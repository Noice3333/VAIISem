<?php

namespace App\Controllers;

use App\Models\User;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\DB\Connection;
use Exception;
use PDO;
use JsonException;

/**
 * Class HomeController
 * Handles actions related to the home page and other public actions.
 *
 * This controller includes actions that are accessible to all users, including a default landing page and a contact
 * page. It provides a mechanism for authorizing actions based on user permissions.
 *
 * @package App\Controllers
 */
class HomeController extends BaseController
{
    /**
     * Authorizes controller actions based on the specified action name.
     *
     * In this implementation, all actions are authorized unconditionally.
     *
     * @param string $action The action name to authorize.
     * @return bool Returns true, allowing all actions.
     */
    public function authorize(string $action): bool
    {
        return true;
    }

    /**
     * Displays the default home page.
     *
     * This action serves the main HTML view of the home page.
     *
     * @return Response The response object containing the rendered HTML for the home page.
     */
    public function index(Request $request): Response
    {
        return $this->html();
    }

    /**
     * Displays the contact page.
     *
     * This action serves the HTML view for the contact page, which is accessible to all users without any
     * authorization.
     *
     * @return Response The response object containing the rendered HTML for the contact page.
     */
    public function contact(Request $request): Response
    {
        return $this->html();
    }

    public function post(Request $request): Response
    {
        // If client requests JSON (explicit ?json=1 or wants JSON/has JSON body) handle as API
        $isJsonRequest = ($request->get('json') === '1') || $request->isJson() || $request->wantsJson();
        if ($isJsonRequest) {
            // Use DB connection
            try {
                $conn = Connection::getInstance();
            } catch (Exception $e) {
                return $this->json(['error' => 'DB connection error'])->setStatusCode(500);
            }

            if ($request->isGet()) {
                try {
                    //$stmt = $conn->prepare('SELECT id, user_id, text, latitude, longitude, created_at FROM posts ORDER BY created_at DESC');
                    //$stmt->execute([]);
                    //$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    //return $this->json($rows);
                    $rows = \App\Models\Post::getAll(null, [], 'created_at DESC');
                    return $this->json(array_map(function($p){
                        $user = null;
                        if ($p->getUserId() !== null) {
                            try { $user = \App\Models\User::getOne($p->getUserId()); } catch (\Throwable $t) { $user = null; }
                        }
                        $username = $user ? ($user->getUsername() ?? ($user->login ?? null)) : null;
                        return [
                            'id' => $p->getId(),
                            'user_id' => $p->getUserId(),
                            'username' => $username,
                            'title' => $p->getTitle(),
                            'description' => $p->getDescription(),
                            'image' => $p->getImage(),
                            'latitude' => $p->getLatitude(),
                            'longitude' => $p->getLongitude(),
                            'created_at' => $p->getCreatedAt()
                        ];
                    }, $rows));
                } catch (Exception $e) {
                    return $this->json(['error' => 'Query failed'])->setStatusCode(500);
                }
            }

            if ($request->isPost()) {
                try {
                    $input = $request->json();
                } catch (JsonException $je) {
                    return $this->json(['error' => 'Invalid JSON'])->setStatusCode(400);
                }

                // Normalize object => array
                if (is_object($input)) {
                    $input = json_decode(json_encode($input), true);
                }

                // Support alternate naming lat/lng or latitude/longitude
                $latKey = array_key_exists('lat', $input) ? 'lat' : (array_key_exists('latitude', $input) ? 'latitude' : null);
                $lngKey = array_key_exists('lng', $input) ? 'lng' : (array_key_exists('longitude', $input) ? 'longitude' : null);

                if (!isset($input['text'], $latKey, $lngKey)) {
                    return $this->json(['error' => 'Missing fields'])->setStatusCode(400);
                }

                // ensure text is a string
                $text = trim((string)($input['text'] ?? ''));
                $lat = (float)$input[$latKey];
                $lng = (float)$input[$lngKey];

                if ($text === '' || abs($lat) > 90 || abs($lng) > 180) {
                    return $this->json(['error' => 'Invalid data'])->setStatusCode(400);
                }

                try {
                    //session_start();
                    $userId = isset($_SESSION['user']) && method_exists($_SESSION['user'], 'getId') ? $_SESSION['user']->getId() : null;

                    $post = \App\Models\Post::create([
                        'user_id' => $userId,
                        'text' => $text,
                        'latitude' => $lat,
                        'longitude' => $lng
                    ]);

                    return $this->json(['ok' => true, 'id' => $post->getId()])->setStatusCode(201);
                } catch (Exception $e) {
                    return $this->json(['error' => 'Insert failed'])->setStatusCode(500);
                }
            }

            return $this->json(['error' => 'Method not allowed'])->setStatusCode(405);
        }

        // Non-API fallback: return HTML view
        return $this->html();
    }

    public function account(Request $request): Response
    {
        $message = null;
        if ($request->hasValue('submit')) {
            $userId = $_SESSION['user']->getId();
            $message = User::edit(
                $userId,
                $request->value('name'),
                $request->value('login'),
                $request->value('password')
            );
            if ($message === null) {
                $message = "Changes made.";
            }
        } else if ($request->hasValue('delete')) {
            $userId = $_SESSION['user']->getId();
            $message = User::deleteAccount($userId);
            if ($message === null) {
                return $this->redirect($this->url("home.index"));
            }
        }
        return $this->html([
            'message' => $message
        ]);
    }

    // Removed register, edit, delete methods from controller. All business logic is now in the User model.
}
