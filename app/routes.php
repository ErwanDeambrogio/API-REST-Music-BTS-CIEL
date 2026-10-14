
<?php

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Middleware\JwtHelper;
use App\Middleware\JwtMiddleware;
use App\Repository\ArtistRepository;
use App\Repository\AlbumRepository;
use App\Repository\RatingRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    // =========================================
    // ROUTES DE BASE
    // =========================================

    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    // Récupérer tous les artistes
    $app->get('/GetAllArtist', function (Request $request, Response $response) {
        $db = $this->get(\PDO::class);

        $sth = $db->prepare("SELECT * FROM `artists`");
        $sth->execute();

        $response->getBody()->write(
            json_encode($sth->fetchAll(\PDO::FETCH_ASSOC))
        );

        return $response->withHeader('Content-Type', 'application/json');
    });

    // Récupérer un artiste par ID
    $app->get('/getArtistById/{id}', function (Request $request, Response $response, array $args) {
        $db = $this->get(\PDO::class);

        $sth = $db->prepare(
            "SELECT * FROM `artists` WHERE idArtist = :idartist"
        );

        $sth->execute([
            'idartist' => (int) $args['id']
        ]);

        $artist = $sth->fetch(\PDO::FETCH_ASSOC);

        $response->getBody()->write(
            json_encode($artist ?: ['error' => 'Artiste introuvable'])
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($artist ? 200 : 404);
    });

    // Ajouter un artiste (JWT obligatoire)
    $app->post('/addArtist', function (Request $request, Response $response) {
        $input = (array) $request->getParsedBody();

        $name = $input['Name'] ?? null;
        $annee = $input['Annee'] ?? null;
        $description = $input['Description'] ?? '';

        if (!$name) {
            $response->getBody()->write(
                json_encode(['error' => 'Le champ Name est obligatoire'])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $db = $this->get(\PDO::class);

        $sth = $db->prepare(
            "INSERT INTO `artists` (`Name`, `Annee`, `Description`)
             VALUES (:name, :annee, :description)"
        );

        $sth->execute([
            'name' => $name,
            'annee' => $annee,
            'description' => $description
        ]);

        $response->getBody()->write(json_encode([
            'message' => 'Artiste ajouté avec succès',
            'idArtist' => $db->lastInsertId()
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);

    })->add(new JwtMiddleware());

    // =========================================
    // AUTHENTIFICATION JWT
    // =========================================

    // Connexion et génération du token JWT
    $app->post('/login', function (Request $request, Response $response) {
        $params = (array) $request->getParsedBody();

        $username = (string) ($params['username'] ?? '');
        $password = (string) ($params['password'] ?? '');

        $expectedUsername = getenv('API_USERNAME');
        $expectedPassword = getenv('API_PASSWORD');

        if (
            $expectedUsername !== false &&
            $expectedPassword !== false &&
            $expectedUsername !== '' &&
            $expectedPassword !== '' &&
            hash_equals($expectedUsername, $username) &&
            hash_equals($expectedPassword, $password)
        ) {
            $token = JwtHelper::generateToken([
                'id' => 1,
                'username' => $username
            ]);

            $response->getBody()->write(
                json_encode(['token' => $token])
            );

            return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(
            json_encode(['error' => 'Invalid credentials'])
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    });

    // Vérifier le JWT
    $app->get('/protected', function (Request $request, Response $response) {

        $response->getBody()->write(json_encode([
            'message' => 'Accès autorisé',
            'user' => $request->getAttribute('user')
        ]));

        return $response->withHeader('Content-Type', 'application/json');

    })->add(new JwtMiddleware());

    // =========================================
    // REPOSITORIES - ROUTES PROTÉGÉES JWT
    // =========================================

    $app->group('/api', function (Group $group) {

        // Tous les artistes
        $group->get('/artists', function (Request $request, Response $response) {
            $repo = $this->get(ArtistRepository::class);

            $response->getBody()->write(
                json_encode($repo->findAll())
            );

            return $response->withHeader('Content-Type', 'application/json');
        });

        // FONCTION SPÉCIFIQUE 1 : artistes par année
        $group->get('/artists/year/{annee}', function (Request $request, Response $response, array $args) {
            $repo = $this->get(ArtistRepository::class);

            $response->getBody()->write(
                json_encode($repo->findByYear((int) $args['annee']))
            );

            return $response->withHeader('Content-Type', 'application/json');
        });

        // FONCTION SPÉCIFIQUE 2 : artistes par nom
        $group->get('/artists/name/{name}', function (Request $request, Response $response, array $args) {
            $repo = $this->get(ArtistRepository::class);

            $response->getBody()->write(
                json_encode($repo->findByName($args['name']))
            );

            return $response->withHeader('Content-Type', 'application/json');
        });

        // FONCTION SPÉCIFIQUE 3 : albums d'un artiste
        $group->get('/albums/artist/{id}', function (Request $request, Response $response, array $args) {
            $repo = $this->get(AlbumRepository::class);

            $response->getBody()->write(
                json_encode($repo->findByArtist((int) $args['id']))
            );

            return $response->withHeader('Content-Type', 'application/json');
        });

        // FONCTION SPÉCIFIQUE 4 : notes d'un album
        $group->get('/ratings/album/{id}', function (Request $request, Response $response, array $args) {
            $repo = $this->get(RatingRepository::class);

            $response->getBody()->write(
                json_encode($repo->findByAlbum((int) $args['id']))
            );

            return $response->withHeader('Content-Type', 'application/json');
        });

        // Un artiste par ID
        $group->get('/artists/{id}', function (Request $request, Response $response, array $args) {
            $artist = $this->get(ArtistRepository::class)
                ->findById((int) $args['id']);

            $response->getBody()->write(
                json_encode($artist ?? ['error' => 'Artiste introuvable'])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($artist ? 200 : 404);
        });

        // Ajouter un artiste avec le repository
        $group->post('/artists', function (Request $request, Response $response) {
            $id = $this->get(ArtistRepository::class)
                ->insert((array) $request->getParsedBody());

            $response->getBody()->write(
                json_encode(['idArtist' => $id])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        });

    })->add(new JwtMiddleware());

    // =========================================
    // ROUTES DU SQUELETTE SLIM
    // =========================================

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });
};
