<?php

namespace App\Repository;

class AlbumRepository extends BaseRepository
{
    protected string $table = 'albums';
    protected string $primaryKey = 'idAlbums';
    protected array $columns = ['Titre', 'Artist_idArtist'];

    // Fonction spécifique 3 : rechercher les albums d'un artiste
    public function findByArtist(int $id): array
    {
        $sth = $this->db->prepare(
            "SELECT * FROM `albums` WHERE `Artist_idArtist` = :id"
        );

        $sth->execute(['id' => $id]);

        return array_map(
            [$this, 'hydrate'],
            $sth->fetchAll(\PDO::FETCH_ASSOC)
        );
    }
}
