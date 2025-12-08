<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

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
        return $this->html();
    }

    public function account(Request $request): Response
    {
        $logged = null;
        $message = null;
        if ($request->hasValue('submit')) {
            $logged = $this->app->getAuth()->edit($request->value('name'),
                $request->value('login'),
                $request->value('password'));
            switch($logged) {
                case 1:
                    break;
                case 0:
                    $message = "Changes made.";
                    break;
                case -1:
                    $message = "User not found (this should not happen)";
                    break;
                case -2:
                    $message = "Edit failed (server error)";
                    break;
                case -3:
                    $message = "Username already taken";
                    break;
            }
        } else if ($request->hasValue('delete')) {
            $logged = $this->app->getAuth()->delete();
            if (!$logged) {
                $message = "Deletion failed (server error)";
            }
            return $this->redirect($this->url("home.index"));
        }

        return $this->html(
            [
                'message' => $message
            ]);
    }
}
