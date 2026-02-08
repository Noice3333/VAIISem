<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Class AdminController
 *
 * This controller manages admin-related actions within the application.It extends the base controller functionality
 * provided by BaseController.
 *
 * @package App\Controllers
 */
class UserController extends BaseController
{
    /**
     * Authorizes actions in this controller.
     *
     * This method checks if the user is logged in, allowing or denying access to specific actions based
     * on the authentication state.
     *
     * @param string $action The name of the action to authorize.
     * @return bool Returns true if the user is logged in; false otherwise.
     */
    public function authorize(string $action): bool
    {
        return $this->app->getAuth()->isLogged();
    }

    /**
     * Displays the index page of the admin panel.
     *
     * This action requires authorization. It returns an HTML response for the admin dashboard or main page.
     *
     * @return \Framework\Http\Responses\Response Returns a response object containing the rendered HTML.
     */
    public function index(Request $request): Response
    {
        return $this->html();
    }

    /**
     * Displays all comments made by the current user.
     *
     * This action requires authorization. It returns an HTML response showing the user's comments
     * with options to delete them.
     *
     * @return \Framework\Http\Responses\Response Returns a response object containing the rendered HTML.
     */
    public function comments(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $userId = null;
        $comments = [];

        if ($auth !== null && $auth->user !== null && method_exists($auth->user, 'getId')) {
            $userId = $auth->user->getId();
        }

        if ($userId !== null && $userId > 0) {
            try {
                // Fetch all comments by this user
                $allComments = \App\Models\Comment::getAll('user_id = ?', [$userId], 'created_at DESC');

                if (is_array($allComments)) {
                    // Build enriched comment data
                    foreach ($allComments as $comment) {
                        if ($comment === null) {
                            continue;
                        }

                        $commentData = [
                            'id' => $comment->getId(),
                            'content' => $comment->getContent(),
                            'created_at' => $comment->getCreatedAt(),
                            'post_id' => $comment->getPostId(),
                            'post' => null,
                            'like_count' => 0,
                            'username' => 'Unknown'
                        ];

                        // Get username from session or user object
                        if ($auth !== null && $auth->user !== null) {
                            if (method_exists($auth->user, 'getUsername')) {
                                $commentData['username'] = $auth->user->getUsername() ?? $commentData['username'];
                            } elseif (method_exists($auth->user, 'getName')) {
                                $commentData['username'] = $auth->user->getName() ?? $commentData['username'];
                            }
                        }

                        $postId = $comment->getPostId();
                        if ($postId !== null) {
                            $post = \App\Models\Post::getOne($postId);
                            if ($post !== null) {
                                $commentData['post'] = $post;
                            }
                        }

                        $commentId = $comment->getId();
                        if ($commentId !== null) {
                            $commentData['like_count'] = \App\Models\Like::countForTarget('comment', $commentId);
                        }

                        $comments[] = $commentData;
                    }
                }
            } catch (\Exception $e) {
                error_log('Error fetching comments: ' . $e->getMessage());
                $comments = [];
            }
        }

        return $this->html(['comments' => $comments]);
    }

    /**
     * Displays all posts and comments liked by the current user.
     *
     * This action requires authorization. It returns an HTML response showing all items the user has liked.
     *
     * @return \Framework\Http\Responses\Response Returns a response object containing the rendered HTML.
     */
    public function likes(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $userId = null;
        $likedPosts = [];
        $likedComments = [];

        if ($auth !== null && $auth->user !== null && method_exists($auth->user, 'getId')) {
            $userId = $auth->user->getId();
        }

        if ($userId !== null && $userId > 0) {
            try {
                // Fetch all likes by this user
                $likes = \App\Models\Like::getAll('user_id = ?', [$userId], 'created_at DESC');

                if (is_array($likes)) {
                    // Separate posts and comments
                    foreach ($likes as $like) {
                        if ($like === null) {
                            continue;
                        }

                        $targetType = $like->getTargetType();
                        $targetId = $like->getTargetId();

                        if ($targetType === 'post' && $targetId !== null) {
                            $post = \App\Models\Post::getOne($targetId);
                            if ($post !== null && method_exists($post, 'getId')) {
                                $postId = $post->getId();
                                if ($postId !== null) {
                                    // Fetch post author username
                                    $authorUsername = 'Unknown';
                                    $postUserId = $post->getUserId();
                                    if ($postUserId !== null) {
                                        try {
                                            $author = \App\Models\User::getOne($postUserId);
                                            if ($author !== null) {
                                                if (method_exists($author, 'getUsername')) {
                                                    $authorUsername = $author->getUsername() ?? $authorUsername;
                                                } elseif (method_exists($author, 'getName')) {
                                                    $authorUsername = $author->getName() ?? $authorUsername;
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            // keep default
                                        }
                                    }

                                    $postData = [
                                        'id' => $postId,
                                        'title' => $post->getTitle(),
                                        'description' => $post->getDescription(),
                                        'image' => $post->getImage(),
                                        'created_at' => $post->getCreatedAt(),
                                        'latitude' => $post->getLatitude(),
                                        'longitude' => $post->getLongitude(),
                                        'user_id' => $post->getUserId(),
                                        'username' => $authorUsername,
                                        'like_count' => \App\Models\Like::countForTarget('post', $postId),
                                        'is_owned_by_current_user' => ($post->getUserId() === $userId)
                                    ];
                                    $likedPosts[] = $postData;
                                }
                            }
                        } elseif ($targetType === 'comment' && $targetId !== null) {
                            $comment = \App\Models\Comment::getOne($targetId);
                            if ($comment !== null && method_exists($comment, 'getId')) {
                                $commentId = $comment->getId();
                                if ($commentId !== null) {
                                    // Fetch comment author username
                                    $authorUsername = 'Unknown';
                                    $commentUserId = $comment->getUserId();
                                    if ($commentUserId !== null) {
                                        try {
                                            $author = \App\Models\User::getOne($commentUserId);
                                            if ($author !== null) {
                                                if (method_exists($author, 'getUsername')) {
                                                    $authorUsername = $author->getUsername() ?? $authorUsername;
                                                } elseif (method_exists($author, 'getName')) {
                                                    $authorUsername = $author->getName() ?? $authorUsername;
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            // keep default
                                        }
                                    }

                                    $commentData = [
                                        'id' => $commentId,
                                        'content' => $comment->getContent(),
                                        'created_at' => $comment->getCreatedAt(),
                                        'user_id' => $comment->getUserId(),
                                        'username' => $authorUsername,
                                        'post_id' => $comment->getPostId(),
                                        'post' => null,
                                        'like_count' => \App\Models\Like::countForTarget('comment', $commentId),
                                        'is_owned_by_current_user' => ($comment->getUserId() === $userId)
                                    ];

                                    $postId = $comment->getPostId();
                                    if ($postId !== null) {
                                        $post = \App\Models\Post::getOne($postId);
                                        if ($post !== null) {
                                            $commentData['post'] = $post;
                                        }
                                    }

                                    $likedComments[] = $commentData;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                error_log('Error fetching likes: ' . $e->getMessage());
                $likedPosts = [];
                $likedComments = [];
            }
        }

        return $this->html(['likedPosts' => $likedPosts, 'likedComments' => $likedComments, 'userId' => $userId]);
    }
}
