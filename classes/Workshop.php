<?php
/**
 * Klasse zur Verwaltung von Werkstätten.
 * Ermöglicht die Suche nach Werkstätten mit Filtern.
 */
require_once __DIR__ . '/Database.php';

class Workshop
{
    /** @var PDO Datenbankverbindung */
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Findet Werkstätten in der Nähe mit optionalen Filtern.
     *
     * @param float $lat Breitengrad des Standorts
     * @param float $lng Längengrad des Standorts
     * @param int $radiusKm Suchradius in Kilometern (Standard: 25)
     * @param string|null $type Filter nach Werkstatttyp (z. B. 'freie', 'marken', 'mobile')
     * @param string|null $specialization Filter nach Spezialisierung (z. B. 'motor', 'elektrik')
     * @return array Liste der passenden Werkstätten
     */
    public function findNearby(
        float $lat,
        float $lng,
        int $radiusKm = 25,
        ?string $type = null,
        ?string $specialization = null
    ): array {
        $sql = "
            SELECT 
                id, name, address, lat, lng, type, specialization, phone, email, website,
                (
                    6371 * acos(
                        cos(radians(:lat)) * cos(radians(lat)) * cos(radians(lng) - radians(:lng)) +
                        sin(radians(:lat)) * sin(radians(lat))
                    )
                ) AS distance
            FROM workshops
            WHERE (
                6371 * acos(
                    cos(radians(:lat)) * cos(radians(lat)) * cos(radians(lng) - radians(:lng)) +
                    sin(radians(:lat)) * sin(radians(lat))
                )
            ) <= :radius
        ";

        $params = [
            ':lat'   => $lat,
            ':lng'  => $lng,
            ':radius' => $radiusKm,
        ];

        if ($type) {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }

        if ($specialization) {
            $sql .= " AND specialization ILIKE :specialization";
            $params[':specialization'] = '%' . $specialization . '%';
        }

        $sql .= " ORDER BY distance ASC LIMIT 20";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
