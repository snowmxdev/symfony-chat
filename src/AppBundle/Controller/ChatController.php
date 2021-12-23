<?php

namespace AppBundle\Controller;

use AppBundle\Utils\Channel;
use AppBundle\Utils\ChatConfig;
use AppBundle\Utils\Message;
use AppBundle\Utils\UserOnline;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ChatController extends Controller
{
    /**
     * @Route("/chat/", name="chat_index")
     *
     * Show main window
     *
     * Get messages from last 24h and users online then show chat's main window,
     * last messages and send variables to twig to configure var to jQuery
     *
     * @param Request $request
     * @param Message $message
     * @param UserOnline $userOnline
     * @param Channel $channelService
     * @param ChatConfig $config
     *
     * @param SessionInterface $session
     *
     * @return Response Return main page with all start information
     */
    public function showAction(
        Request $request,
        Message $message,
        UserOnline $userOnline,
        Channel $channelService,
        ChatConfig $config,
        SessionInterface $session
    ): Response {
        $user = $this->getUser();
        $channel = $session->get('channel');
        if (!$channelService->checkIfUserCanBeOnThatChannel($user, $channel)) {
            $channel = 1;
            $session->set('channel', 1);
        }

        $userOnline->updateUserOnline($user, $channel, 0);

        return $this->render('chat/index.html.twig',[
            'messages' => $message->getMessagesInIndex($user),
            'usersOnline' => $userOnline->getOnlineUsers($user->getId(), $channel),
            'user' => $user,
            'user_channel' => $channel,
            'channels' => $config->getChannels($user),
            'locale' => $request->getLocale()
        ]);
    }

    /**
     * @Route("/chat/add/", name="chat_add")
     *
     * Add new message
     *
     * Check if message can be added to database and get messages that was wrote between
     * last refresh and calling this method
     *
     * @param Request $request A Request instance
     * @param Message $message
     *
     * @return JsonResponse returns status success or failure and new messages
     */
    public function addAction(Request $request, Message $message): Response
    {
        $messageText = $request->get('text');
        $user = $this->getUser();

        $status = $message->addMessageToDatabase($user, $messageText);

        return $this->json($status);
    }

    /**
     * @Route("/chat/refresh/", name="chat_refresh")
     *
     * Refresh chat
     *
     * Get new messages from last refresh and get users online
     *
     * @param Request $request
     * @param Message $messageService
     * @param UserOnline $userOnlineService
     * @param Channel $channel
     * @param SessionInterface $session
     *
     * @return JsonResponse returns messages and users online
     */
    public function refreshAction(
        Request $request,
        Message $messageService,
        UserOnline $userOnlineService,
        Channel $channel,
        SessionInterface $session
    ): Response {
        $messages = $messageService->getMessagesFromLastId($this->getUser());
        $typing = $request->request->get('typing');
        $typing = in_array($typing, [0,1]) ? $typing : 0;

        $changeChannel = 0;
        $userOnlineService->updateUserOnline($this->getUser(), $session->get('channel'), $typing);

        if (!$channel->checkIfUserCanBeOnThatChannel($this->getUser(), $session->get('channel'))) {
            $session->set('channel', 1);
            $session->set('channelChanged', 1);
            $changeChannel = 1;
        }

        $usersOnline = $userOnlineService
            ->getOnlineUsers(
            $this->getUser()->getId(),
            $session->get('channel')
        );

        $return = [
            'messages' => $messages,
            'usersOnline' => $usersOnline,
            'kickFromChannel' => $changeChannel
        ];
        return new JsonResponse($return);
    }

    /**
     * @Route("/chat/delete/", name="chat_delete")
     * @Security("has_role('ROLE_MODERATOR')")
     *
     * Delete message from database
     *
     * Checking if message exists in database and then delete it from database,
     * add message to database that message was deleted and by whom
     *
     * @param Request $request A Request instance
     * @param Message $message
     *
     * @return JsonResponse status true or false
     */
    public function deleteAction(Request $request, Message $message): Response
    {
        $id = $request->get('messageId');
        $user = $this->getUser();
        if (!$id) {
            return $this->json(['status' => 0]);
        }

        $status = $message->deleteMessage($id, $user);

        return $this->json(['status' => $status]);
    }

    /**
     * @Route("/chat/logout", name="chat_logout")
     *
     * Logout from chat
     * Delete User's info from online users in database and then redirect to logout in fosuserbundle
     *
     * @param UserOnline $userOnlineService
     *
     * @return RedirectResponse Redirect to fos logout
     */
    public function logoutAction(UserOnline $userOnlineService): Response
    {
        $userOnlineService->deleteUserWhenLogout($this->getUser()->getId());

        return $this->redirectToRoute('fos_user_security_logout');
    }

    /**
     * @Route("/chat/channel", name="change_channel_chat")
     *
     * Change channel on chat
     *
     * Checking if channel exists and change user's channel in session
     *
     * @param Request $request A Request instance
     * @param Channel $channelService
     *
     * @return JsonResponse returns status of changing channel
     */
    public function changeChannelAction(Request $request, Channel $channelService): Response
    {
        $channel = $request->request->get('channel');
        if (!$channel) {
            return $this->json('false');
        }
        $return = $channelService->changeChannelOnChat($this->getUser(), $channel);

        return $this->json($return);
    }
}