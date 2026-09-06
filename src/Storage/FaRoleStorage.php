<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Rbac\Storage;

use Ksfraser\CommonDb\Contract\DbConnectionInterface;

/**
 * FaRoleStorage - FA database implementation of RoleStorageInterface.
 *
 * Uses FA's existing user/role tables for persistence.
 *
 * @since 1.0.0
 */
class FaRoleStorage implements RoleStorageInterface
{
    /** @var DbConnectionInterface */
    private $db;

    /** @var string */
    private $tablePrefix;

    /**
     * Constructor.
     *
     * @param DbConnectionInterface $db
     * @param string $tablePrefix
     *
     * @since 1.0.0
     */
    public function __construct(DbConnectionInterface $db, string $tablePrefix = '')
    {
        $this->db = $db;
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function findByName(string $name): ?array
    {
        $sql = "SELECT * FROM {$this->tablePrefix}security_roles WHERE role_id = " . $this->db->quote($name);

        $result = $this->db->fetchAssoc($sql);

        if ($result === null) {
            return null;
        }

        return [
            'name' => $result['role_id'],
            'parents' => $this->getParents($name),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        $sql = "SELECT role_id FROM {$this->tablePrefix}security_roles ORDER BY role_id";

        $results = $this->db->fetchAll($sql);

        $roles = [];
        foreach ($results as $row) {
            $roles[] = [
                'name' => $row['role_id'],
                'parents' => $this->getParents($row['role_id']),
            ];
        }

        return $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getParents(string $name): array
    {
        $sql = "SELECT parent_id FROM {$this->tablePrefix}security_roles WHERE role_id = " . $this->db->quote($name);

        $result = $this->db->fetchAssoc($sql);

        if ($result === null || empty($result['parent_id'])) {
            return [];
        }

        return array_filter(explode(',', $result['parent_id']));
    }

    /**
     * {@inheritdoc}
     */
    public function save(string $name, array $parents = []): void
    {
        $parentStr = implode(',', $parents);

        $existing = $this->findByName($name);

        if ($existing === null) {
            $sql = "INSERT INTO {$this->tablePrefix}security_roles (role_id, parent_id) VALUES (" .
                $this->db->quote($name) . ", " . $this->db->quote($parentStr) . ")";
        } else {
            $sql = "UPDATE {$this->tablePrefix}security_roles SET parent_id = " .
                $this->db->quote($parentStr) . " WHERE role_id = " . $this->db->quote($name);
        }

        $this->db->executeUpdate($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $name): void
    {
        $sql = "DELETE FROM {$this->tablePrefix}security_roles WHERE role_id = " . $this->db->quote($name);

        $this->db->executeUpdate($sql);
    }
}
