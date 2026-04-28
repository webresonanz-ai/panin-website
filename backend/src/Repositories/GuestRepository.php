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
            $clauses[] = '(full_name LIKE :search OR email LIKE :search OR suite LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status === 'vip') {
            $clauses[] = 'vip_status = 1';
        } elseif ($status) {
            $clauses[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT id, full_name, email, phone, suite, check_in, check_out, status, special_requests, vip_status, created_at, updated_at
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
            'SELECT id, full_name, email, phone, suite, check_in, check_out, status, special_requests, vip_status, created_at, updated_at
             FROM guests WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $guest = $statement->fetch();

        return $guest ? $this->mapGuest($guest) : null;
    }

    public function create(array $data): array
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO guests (full_name, email, phone, suite, check_in, check_out, status, special_requests, vip_status, created_at, updated_at)
             VALUES (:full_name, :email, :phone, :suite, :check_in, :check_out, :status, :special_requests, :vip_status, NOW(), NOW())'
        );
        $statement->execute([
            'full_name' => $data['fullName'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'suite' => $data['suite'],
            'check_in' => $data['checkIn'],
            'check_out' => $data['checkOut'],
            'status' => $data['status'],
            'special_requests' => $data['specialRequests'] ?: null,
            'vip_status' => $data['vipStatus'] ? 1 : 0,
        ]);

        return $this->find((int) $this->database->connection()->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE guests
             SET full_name = :full_name,
                 email = :email,
                 phone = :phone,
                 suite = :suite,
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
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'suite' => $data['suite'],
            'check_in' => $data['checkIn'],
            'check_out' => $data['checkOut'],
            'status' => $data['status'],
            'special_requests' => $data['specialRequests'] ?: null,
            'vip_status' => $data['vipStatus'] ? 1 : 0,
        ]);

        return $this->find($id);
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
            'email' => $guest['email'],
            'phone' => $guest['phone'],
            'suite' => $guest['suite'],
            'checkIn' => $guest['check_in'],
            'checkOut' => $guest['check_out'],
            'status' => $guest['status'],
            'specialRequests' => $guest['special_requests'],
            'vipStatus' => (bool) $guest['vip_status'],
            'createdAt' => $guest['created_at'],
            'updatedAt' => $guest['updated_at'],
        ];
    }
}
