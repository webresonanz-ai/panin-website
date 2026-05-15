<?php

namespace App\Repositories;

use App\Core\Database;

class GuestRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    public function all(?string $search = null, ?string $status = null): array
    {
        $clauses = [];
        $params = [];

        if ($search) {
            $clauses[] = '(full_name LIKE :search OR ga_so_position LIKE :search OR seat_number LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status === 'awaitingCheckIn') {
            $clauses[] = 'checked_in_at IS NULL';
        } elseif ($status === 'checkedIn') {
            $clauses[] = 'checked_in_at IS NOT NULL';
        } elseif ($status) {
            $clauses[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT id, full_name, registration_number, ga_so_position, seat_number, phone_number, created_at, updated_at
                , wa_sent_time, checked_in_at, check_in_method
                FROM guests';

        if ($clauses !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $sql .= ' ORDER BY full_name ASC, id DESC';

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($params);

        return array_map([$this, 'mapGuest'], $statement->fetchAll());
    }

    public function find(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, full_name, registration_number, ga_so_position, seat_number, phone_number, created_at, updated_at, wa_sent_time, checked_in_at, check_in_method
             FROM guests WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $guest = $statement->fetch();

        return $guest ? $this->mapGuest($guest) : null;
    }

    public function findAllPendingInvitation(): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, full_name, registration_number, ga_so_position, seat_number, phone_number, created_at, updated_at, wa_sent_time, checked_in_at, check_in_method
             FROM guests
             WHERE wa_sent_time IS NULL OR wa_sent_time = \'\'
             ORDER BY full_name ASC, id DESC'
        );
        $statement->execute();

        return array_map([$this, 'mapGuest'], $statement->fetchAll());
    }

    public function create(array $data): array
    {
        $connection = $this->database->connection();
        $statement = $connection->prepare(
            'INSERT INTO guests (full_name, ga_so_position, seat_number, phone_number, created_at, updated_at)
             VALUES (:full_name, :ga_so_position, :seat_number, :phone_number, NOW(), NOW())'
        );
        $statement->execute([
            'full_name' => $data['fullName'],
            'ga_so_position' => $data['gaSoPosition'] ?: null,
            'seat_number' => $data['seatNumber'] ?: null,
            'phone_number' => $data['phoneNumber'] ?: null,
        ]);

        $guestId = (int) $connection->lastInsertId();
        $this->assignRegistrationNumber($guestId);

        return $this->find($guestId);
    }

    public function findByRegistrationNumber(string $registrationNumber): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, full_name, registration_number, ga_so_position, seat_number, phone_number, created_at, updated_at, wa_sent_time, checked_in_at, check_in_method
             FROM guests WHERE registration_number = :registration_number LIMIT 1'
        );
        $statement->execute(['registration_number' => $registrationNumber]);

        $guest = $statement->fetch();

        return $guest ? $this->mapGuest($guest) : null;
    }

    public function update(int $id, array $data): ?array
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE guests
             SET full_name = :full_name,
                 ga_so_position = :ga_so_position,
                 seat_number = :seat_number,
                 phone_number = :phone_number,
                 checked_in_at = :checked_in_at,
                 check_in_method = :check_in_method,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'full_name' => $data['fullName'],
            'ga_so_position' => $data['gaSoPosition'] ?: null,
            'seat_number' => $data['seatNumber'] ?: null,
            'phone_number' => $data['phoneNumber'] ?? null,
            'checked_in_at' => $data['checkedInAt'] ?? null,
            'check_in_method' => $data['checkInMethod'] ?? null,
        ]);

        return $this->find($id);
    }

    public function import(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $connection = $this->database->connection();
        $statement = $connection->prepare(
            'INSERT INTO guests (full_name, ga_so_position, seat_number, phone_number, created_at, updated_at)
             VALUES (:full_name, :ga_so_position, :seat_number, :phone_number, NOW(), NOW())'
        );

        $connection->beginTransaction();

        try {
            foreach ($rows as $row) {
                $statement->execute([
                    'full_name' => $row['fullName'],
                    'ga_so_position' => $row['gaSoPosition'] ?: null,
                    'seat_number' => $row['seatNumber'] ?: null,
                    'phone_number' => $row['phoneNumber'] ?: null,
                ]);

                $this->assignRegistrationNumber((int) $connection->lastInsertId());
            }

            $connection->commit();

            return count($rows);
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $statement = $this->database->connection()->prepare('DELETE FROM guests WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function saveWaSentTime(int $id, ?string $sentAt = null): void
    {
        $timestamp = $sentAt ?? $this->currentTimestamp();

        $statement = $this->database->connection()->prepare(
            'UPDATE guests
             SET wa_sent_time = :wa_sent_time,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'wa_sent_time' => $timestamp,
        ]);
    }

    public function markCheckedIn(int $id, string $method = 'qr_scan'): ?array
    {
        $checkedInAt = $this->currentTimestamp();

        $statement = $this->database->connection()->prepare(
            'UPDATE guests
             SET checked_in_at = COALESCE(checked_in_at, :checked_in_at),
                 check_in_method = COALESCE(check_in_method, :check_in_method),
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'checked_in_at' => $checkedInAt,
            'check_in_method' => $method,
        ]);

        return $this->find($id);
    }

    private function mapGuest(array $guest): array
    {
        return [
            'id' => (int) $guest['id'],
            'fullName' => $guest['full_name'],
            'registrationNumber' => $guest['registration_number'],
            'gaSoPosition' => $guest['ga_so_position'],
            'seatNumber' => $guest['seat_number'],
            'phoneNumber' => $guest['phone_number'],
            'waSentTime' => $guest['wa_sent_time'],
            'checkedInAt' => $guest['checked_in_at'],
            'checkInMethod' => $guest['check_in_method'],
            'isCheckedIn' => $guest['checked_in_at'] !== null,
            'createdAt' => $guest['created_at'],
            'updatedAt' => $guest['updated_at'],
        ];
    }

    private function assignRegistrationNumber(int $guestId): void
    {
        $guest = $this->database->connection()->prepare(
            'SELECT id, created_at FROM guests WHERE id = :id LIMIT 1'
        );
        $guest->execute(['id' => $guestId]);
        $record = $guest->fetch();

        if (!$record) {
            return;
        }

        $createdAtTimestamp = strtotime((string) $record['created_at']);
        $createdAtToken = $createdAtTimestamp === false ? time() : $createdAtTimestamp;
        $randomSuffix = $this->randomRegistrationSuffix();

        $statement = $this->database->connection()->prepare(
            'UPDATE guests
             SET registration_number = :registration_number
             WHERE id = :id AND (registration_number IS NULL OR registration_number = \'\')'
        );

        $statement->execute([
            'registration_number' => sprintf('PANIN_%d_%s_%s', $guestId, $createdAtToken, $randomSuffix),
            'id' => $guestId,
        ]);
    }

    private function randomRegistrationSuffix(): string
    {
        return strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }

    private function currentTimestamp(): string
    {
        return date('Y-m-d H:i:s');
    }
}
