<?php

namespace App\Repository;

class RatingRepository extends BaseRepository
{
    protected string $table = 'ratings';
    protected string $primaryKey = 'idRatings';
    protected array $columns = ['Grade', 'Albums_idAlbums'];

    // Fonction spécifique : rechercher les notes d'un album
    public function findByAlbum(int $id): array
    {
        $sth = $this->db->prepare(
            "SELECT * FROM `ratings` WHERE `Albums_idAlbums` = :id"
        );

        $sth->execute(['id' => $id]);

        return array_map(
            [$this, 'hydrate'],
            $sth->fetchAll(\PDO::FETCH_ASSOC)
        );
    }
}
