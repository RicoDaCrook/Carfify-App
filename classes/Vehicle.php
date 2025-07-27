<?php
/**
 * Klasse zur Verwaltung von Fahrzeugen.
 * Stellt Methoden zur Fahrzeugsuche und Detailabfrage bereit.
 */
require_once __DIR__ . '/Database.php';

class Vehicle
{
    /** @var PDO Datenbankverbindung */
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Sucht Fahrzeuge anhand von Kriterien.
     *
     * @param array $filters Suchfilter (z. B. ['brand' => 'VW', 'model' => 'Golf'])
     * @return array Liste der gefundenen Fahrzeuge
     */
    public function search(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['brand'])) {
            $where[] = "brand ILIKE :brand";
            $params[':brand'] = '%' . $filters['brand'] . '%';
        }
        if (!empty($filters['model'])) {
            $where[] = "model ILIKE :model";
            $params[':model'] = '%' . $filters['model'] . '%';
        }
        if (!empty($filters['year_from'])) {
            $where[] = "year >= :year_from";
            $params[':year_from'] = (int)$filters['year_from'];
        }
        if (!empty($filters['year_to'])) {
            $where[] = "year <= :year_to";
            $params[':year_to'] = (int)$filters['year_to'];
        }

        $sql = "SELECT id, brand, model, year, fuel_type, engine_code, image_url FROM vehicles";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY brand, model, year LIMIT 50";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lädt Fahrzeugdetails anhand der ID.
     *
     * @param int $id Fahrzeug-ID
     * @return array|null Fahrzeugdetails oder null, falls nicht gefunden
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM vehicles WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $vehicle = $stmt->fetch();
        return $vehicle ?: null;
    }
}
