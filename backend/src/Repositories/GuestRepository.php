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
            $clauses[] = '(full_name LIKE :search OR company LIKE :search OR position LIKE :search OR seat_number LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status === 'vip') {
            $clauses[] = 'vip_status = 1';
        } elseif ($status) {
            $clauses[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT id, full_name, registration_number, company, position, seat_number, check_in, check_out, status, special_requests, vip_status, created_at, updated_at
                FROM guests';

        if ($clauses !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $sql .= ' ORDER BY check_in ASC, id DESC';

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($params);

        return array_map([$this, 'mapGuest'], $statement->fetchAll());
    }

    public function find(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, full_name, registration_number, company, position, seat_number, check_in, check_out, status, special_requests, vip_status, created_at, updated_at
             FROM guests WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $guest = $statement->fetch();

        return $guest ? $this->mapGuest($guest) : null;
    }

    public function create(array $data): array
    {
        $connection = $this->database->connection();
        $statement = $connection->prepare(
            'INSERT INTO guests (full_name, company, position, seat_number, check_in, check_out, status, special_requests, vip_status, created_at, updated_at)
             VALUES (:full_name, :company, :position, :seat_number, :check_in, :check_out, :status, :special_requests, :vip_status, NOW(), NOW())'
        );
        $statement->execute([
            'full_name' => $data['fullName'],
            'company' => $data['company'] ?: null,
            'position' => $data['position'] ?: null,
            'seat_number' => $data['seatNumber'] ?: null,
            'check_in' => $data['checkIn'],
            'check_out' => $data['checkOut'],
            'status' => $data['status'],
            'special_requests' => $data['specialRequests'] ?: null,
            'vip_status' => $data['vipStatus'] ? 1 : 0,
        ]);

        $guestId = (int) $connection->lastInsertId();
        $this->assignRegistrationNumber($guestId);

        return $this->find($guestId);
    }

    public function update(int $id, array $data): ?array
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE guests
             SET full_name = :full_name,
                 company = :company,
                 position = :position,
                 seat_number = :seat_number,
                 check_in = :check_in,
                 check_out = :check_out,
                 status = :status,
                 special_requests = :special_requests,
                 vip_status = :vip_status,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'full_name' => $data['fullName'],
            'company' => $data['company'] ?: null,
            'position' => $data['position'] ?: null,
            'seat_number' => $data['seatNumber'] ?: null,
            'check_in' => $data['checkIn'],
            'check_out' => $data['checkOut'],
            'status' => $data['status'],
            'special_requests' => $data['specialRequests'] ?: null,
            'vip_status' => $data['vipStatus'] ? 1 : 0,
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
            'INSERT INTO guests (full_name, company, position, seat_number, check_in, check_out, status, special_requests, vip_status, created_at, updated_at)
             VALUES (:full_name, :company, :position, :seat_number, :check_in, :check_out, :status, :special_requests, :vip_status, NOW(), NOW())'
        );

        $connection->beginTransaction();

        try {
            foreach ($rows as $row) {
                $statement->execute([
                    'full_name' => $row['fullName'],
                    'company' => $row['company'] ?: null,
                    'position' => $row['position'] ?: null,
                    'seat_number' => $row['seatNumber'] ?: null,
                    'check_in' => $row['checkIn'],
                    'check_out' => $row['checkOut'],
                    'status' => $row['status'],
                    'special_requests' => $row['specialRequests'] ?: null,
                    'vip_status' => $row['vipStatus'] ? 1 : 0,
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

    private function mapGuest(array $guest): array
    {
        return [
            'id' => (int) $guest['id'],
            'fullName' => $guest['full_name'],
            'registrationNumber' => $guest['registration_number'],
            'company' => $guest['company'],
            'position' => $guest['position'],
            'seatNumber' => $guest['seat_number'],
            'checkIn' => $guest['check_in'],
            'checkOut' => $guest['check_out'],
            'status' => $guest['status'],
            'specialRequests' => $guest['special_requests'],
            'vipStatus' => (bool) $guest['vip_status'],
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
}
