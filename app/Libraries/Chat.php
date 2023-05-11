<?php

namespace App\Libraries;

use App\Models\AddFriendRequest;
use App\Models\BlockedUser;
use App\Models\DeletedMessage;
use App\Models\FileMessages;
use App\Models\Friend;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Models\ViewedMessage;
use CodeIgniter\CLI\CLI;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

use function PHPUnit\Framework\isNull;

function array_find($xs, $f)
{
    foreach ($xs as $x) {
        if (call_user_func($f, $x) === true)
            return $x;
    }
    return null;
}

class Chat implements MessageComponentInterface
{
    public $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        CLI::print('Server Started');
    }

    public function onOpen(ConnectionInterface $conn)
    {
        CLI::print("New connection! ({$conn->resourceId})\n");

        $this->clients->attach($conn);

        $querystring = $conn->httpRequest->getUri()->getQuery();

        parse_str($querystring, $queryarray);



        if (isset($queryarray['id'])) {

            $userModel = new User();
            $friendModel = new Friend();
            $updateConnect = [
                'connectid' => $conn->resourceId,
            ];
            $userModel->update($queryarray['id'], $updateConnect);

            $user_data = $userModel->where('id', $queryarray['id'])->first();

            $user_id = $user_data['id'];

            $data['status_type'] = 'Online';

            $data['user_id_status'] = $user_id;

            $friends = $friendModel->select('friends.user_id, friends.friend_id, users.*')->join('users', "(friends.user_id = users.id and friends.user_id != {$user_data['id']}) or (friends.friend_id = users.id and friends.friend_id != {$user_data['id']})")->where("(friend_id = {$user_data['id']} or user_id = {$user_data['id']} ) and `status` = 'accepted' and id not in (SELECT blocked_user_id FROM blocked_users WHERE user_id = {$user_data['id']}) and id not in (SELECT user_id FROM blocked_users WHERE blocked_user_id = {$user_data['id']})")->findAll();

            foreach ($this->clients as $client) {
                if ($client->resourceId == $conn->resourceId) {
                    $client->send(json_encode([
                        'event' => 'onGetFriends',
                        'friends' => $friends,
                    ]));
                }
            }
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        CLI::print("\n\n" . json_encode($data) . "\n\n");

        try {
            switch ($data['command']) {
                case 'setOwnerGroup': {
                        try {
                            $groupId = $data['groupId'];
                            $userId = $data['userId'];
                            $groupModel = new Group();
                            $userModel = new User();
                            $messageModel = new Message();
                            $connectIds = array();

                            $groupModel->update($groupId, ['owner' => $userId]);


                            $user = $userModel->find($userId);

                            $systemMessageId = $messageModel->insert([
                                'sender_id' => 0,
                                'message' => "{$user['fullname']} đã được bổ nhiệm làm trưởng nhóm",
                                'group_id' => $groupId,
                            ]);


                            $listUserReceiver = $userModel->select('connectid')->where("id IN (SELECT user_id FROM `groups` JOIN group_members on group_id = `groups`.id WHERE `groups`.id = {$groupId})")->get()->getResultArray();

                            foreach ($listUserReceiver as $user) {
                                if (isset($user))
                                    array_push($connectIds, $user['connectid']);
                            }
                            foreach ($this->clients as $client) {
                                if (in_array($client->resourceId, $connectIds) || $from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onNewMessage',
                                        'groupId' => $groupId,
                                        'result' => true,
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "setOwnerGroup"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case "unlockUser": {
                        try {
                            $userId = $data['userId'];
                            $friendId = $data['friendId'];
                            $blockedUser = new BlockedUser();
                            $userModel = new User();
                            $groupMember = new GroupMember();

                            $blockedUser->where("user_id = {$userId} and blocked_user_id = {$friendId}");
                            $blockedUser->delete();

                            $friend = $userModel->find($friendId);

                            $id = $groupMember->select("group_members.group_id")->join("groups", "groups.id = group_members.group_id", 'inner')->where("user_id IN ({$userId}, {$friendId}) and type = 'dou'")->groupBy("group_members.group_id")->having("COUNT(DISTINCT user_id) = 2")->orderBy('group_id', 'DESC')->first();

                            foreach ($this->clients as $client) {
                                if (
                                    $client->resourceId == $from->resourceId || (isset($friend) && $client->resourceId == $friend['connectid'])
                                ) {
                                    $client->send(json_encode([
                                        'event' => 'onUnblockUser',
                                        'groupId' => $id['group_id'],
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "unlockUser"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'blockUser': {
                        try {
                            $userId = $data['userId'];
                            $friendId = $data['friendId'];
                            $userModel = new User();
                            $friendModel = new Friend();
                            $blockedUser = new BlockedUser();
                            $groupMember = new GroupMember();

                            $blockedUser->upsert([
                                'user_id' => $userId,
                                'blocked_user_id' => $friendId,
                            ]);

                            $friendModel->where("(user_id = {$userId} and friend_id = {$friendId}) OR (friend_id = {$userId} AND user_id = {$friendId})");
                            $friendModel->delete();

                            $friend = $userModel->find($friendId);

                            $id = $groupMember->select("group_members.group_id")->join("groups", "groups.id = group_members.group_id", 'inner')->where("user_id IN ({$userId}, {$friendId}) and type = 'dou'")->groupBy("group_members.group_id")->having("COUNT(DISTINCT user_id) = 2")->orderBy('group_id', 'DESC')->first();

                            foreach ($this->clients as $client) {
                                if (
                                    $client->resourceId == $from->resourceId || (isset($friend) && $client->resourceId == $friend['connectid'])
                                ) {
                                    $client->send(json_encode([
                                        'event' => 'onBlockUser',
                                        'groupId' => $id['group_id'],
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "blockUser"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'cancelRequestAddFriend': {
                        try {
                            $userId = $data['userId'];
                            $friendId = $data['friendId'];
                            $userModel = new User();
                            $friendModel = new Friend();
                            $notifyModel = new Notification();


                            $friendModel->where("(user_id = {$userId} and friend_id = {$friendId}) OR (friend_id = {$userId} AND user_id = {$friendId})");
                            $friendModel->delete();

                            $notifies = $notifyModel->select("*")->where("((user_id = {$userId} and from_id = {$friendId}) OR (user_id = {$friendId} and from_id = {$userId})) AND type = 'friend_request'")->get()->getResultArray();

                            if(isset($notifies)) {
                                foreach($notifies as $notify) {
                                    $notifyModel->delete($notify['id']);
                                }
                            }

                            $friend = $userModel->find($friendId);

                            foreach ($this->clients as $client) {
                                if ($from == $client || isset($friend) || $client->resourceId == $friend['connectid']) {
                                    $client->send(json_encode([
                                        'event' => 'cancelRequestAddFriend',
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "cancelRequestAddFriend"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'getNotifications': {
                        try {
                            $notifyModel = new Notification();
                            $notifyReaded = $notifyModel->select('notifications.id, notifications.user_id, type, payload, created_at, seen_at, fullname, from_id, avatar, is_readed')->from('users')->where("user_id = " . $data['userId'] . " and from_id = users.id")->where('is_readed', '1')->orderBy('created_at', 'DESC')->findAll();
                            $notifyUnread = $notifyModel->select('notifications.id, notifications.user_id, type, payload, created_at, seen_at, fullname, from_id, avatar, is_readed')->from('users')->where("user_id = " . $data['userId'] . " and from_id = users.id")->where('is_readed = 0')->orderBy('created_at', 'DESC')->findAll();

                            foreach ($this->clients as $client) {
                                if ($client->resourceId == $from->resourceId) {
                                    $client->send(json_encode([
                                        'event' => 'onGetNotifications',
                                        'notifyReaded' => $notifyReaded,
                                        'notifyUnread' => $notifyUnread,
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "getNotifications"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'readNotify': {
                        try {
                            $notificationModel = new Notification();
                            $notifyIds = $data['notifyIds'];
                            $updateNotifyData = array();

                            foreach ($notifyIds as $notifyId) {
                                array_push($updateNotifyData, [
                                    'id' => $notifyId,
                                    'is_readed' => '1',
                                ]);
                            }

                            if (count($updateNotifyData) > 0) {
                                $notificationModel->upsertBatch($updateNotifyData);
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "readNotify"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'getFriends': {
                        try {
                            $querystring = $from->httpRequest->getUri()->getQuery();

                            parse_str($querystring, $queryarray);

                            if (isset($queryarray['id'])) {

                                $userModel = new User();
                                $friendModel = new Friend();
                                $updateConnect = [
                                    'connectid' => $from->resourceId,
                                ];
                                $userModel->update($queryarray['id'], $updateConnect);

                                $user_data = $userModel->where('id', $queryarray['id'])->first();

                                $user_id = $user_data['id'];

                                $data['status_type'] = 'Online';

                                $data['user_id_status'] = $user_id;

                                $friends = $friendModel->select('users.*')->join('users', "(friends.user_id = users.id and friends.user_id != {$user_data['id']}) or (friends.friend_id = users.id and friends.friend_id != {$user_data['id']})")->where("(friend_id = {$user_data['id']} or user_id = {$user_data['id']} ) and `status` = 'accepted' and id not in (SELECT blocked_user_id FROM blocked_users WHERE user_id = {$user_data['id']})  and id not in (SELECT user_id FROM blocked_users WHERE blocked_user_id = {$user_data['id']})")->findAll();

                                foreach ($this->clients as $client) {
                                    if ($client->resourceId == $from->resourceId) {
                                        $client->send(json_encode([
                                            'event' => 'onGetFriends',
                                            'friends' => $friends,
                                        ]));
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "getFriends"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'sendMessage': {
                        try {
                            $userModel = new User();
                            $groupModel = new Group();
                            $groupMemberModel = new GroupMember();
                            $messageModel = new Message();
                            $connectIds = array();

                            $message =
                                [
                                    'group_id' => $data['groupId'],
                                    'sender_id' => $data['senderId'],
                                    'message' => $data['msg'],
                                ];

                            $chat_message_id = $messageModel->insert($message);

                            $sender_user_data = $userModel->where('id', $data['senderId'])->first();
                            
                            $group = $groupModel->find($data['groupId']);

                            $listUserReceiver = $userModel->select('connectid')->where("id IN (SELECT user_id FROM `groups` JOIN group_members on group_id = `groups`.id WHERE `groups`.id = {$data['groupId']})")->get()->getResultArray();

                            if(isset($group) && $group['type'] == 'dou') {
                                $userInGroup = $groupMemberModel->select('user_id')->where("group_id = {$data['groupId']} AND user_id != {$data['senderId']}")->first();
                                $data['groupId'] = $userInGroup['user_id'];
                            }

                            if (isset($group) && $group['type'] == 'multi') {
                                $data['groupId'] = $group['id'];
                            }

                            foreach ($listUserReceiver as $user) {
                                if (isset($user))
                                    array_push($connectIds, $user['connectid']);
                            }

                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $data['from'] = '';
                                } else {
                                    $data['from'] = $sender_user_data['fullname'];
                                }

                                if (in_array($client->resourceId, $connectIds) || $from == $client) {
                                    $data['event'] = 'onNewMessage';
                                    $data['groupName'] = $group['name'];
                                    $data['type'] = $group['type'];
                                    $client->send(json_encode($data));
                                } else {
                                    // $private_chat_object->setStatus('No');
                                    // $private_chat_object->setChatMessageId($chat_message_id);

                                    // $private_chat_object->update_chat_status();
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "sendMessage"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'addFriend': {
                        try {
                            $userModel = new User();
                            $friend = $userModel->where('id', $data['receiverId'])->first();
                            if ($friend && $data['receiverId'] != $data['senderId']) {
                                $notifyModel = new Notification();
                                $addFriendRequestModel = new Friend();
                                $newNotification = [
                                    'user_id' => $friend['id'],
                                    'type' => 'friend_request',
                                    'payload' => 'Gửi lời mời kết bạn.',
                                    'from_id' => $data['senderId'],
                                ];

                                $newAddFriendRequest = [
                                    'user_id' => $data['senderId'],
                                    'friend_id' => $friend['id'],
                                    'status' => 'pending'
                                ];

                                $newNotifyId = $notifyModel->insert($newNotification);
                                $addFriendRequestModel->upsert($newAddFriendRequest);
                                $newedNotify = $notifyModel->select('notifications.id, notifications.user_id, type, payload, created_at, seen_at, fullname, from_id')->from('users')->where("user_id = " . $friend['id'] . " and user_id = users.id" . " and notifications.id = {$newNotifyId}")->first();

                                foreach ($this->clients as $client) {
                                    if ($from == $client) {
                                        $client->send(json_encode([
                                            'event' => 'onSendAddFriend',
                                            'status' => 'success',
                                            'msg' => 'Gửi lời mời kết bạn thành công',
                                        ]));
                                    }

                                    if ($client->resourceId == $friend['connectid']) {
                                        $client->send(json_encode([
                                            'event' => 'onAcceptFriend',
                                            'status' => 'success',
                                            'notify' => $newedNotify,
                                            'msg' =>  $data['senderName'] . ' gửi lời mời kết bạn.',
                                        ]));
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "addFriend"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'acceptFriend': {
                        try {
                            $groupMember = new GroupMember();
                            $group = new Group();
                            $notifyModel = new Notification();
                            $friendModel = new Friend();
                            $userModel = new User();
                            $notifyId = $data["notifyId"];

                            $updateFriend = [
                                'status' => 'accepted'
                            ];

                            $sender = $userModel->find($data['senderId']);
                            $receiver = $userModel->find($data['receiverId']);

                            $newNotify = [
                                'user_id' => $data['senderId'],
                                'type' => 'friend_request_accepted',
                                'from_id' => $data['receiverId'],
                            ];

                            $updateNotify = [
                                'type' => 'new_message',
                                'payload' => "Bạn và {$sender['fullname']} đã trở thành bạn bè.",
                                'from_id' => 0,
                            ];

                            $friendModel->set($updateFriend);
                            $friendModel->where('user_id', $data['senderId']);
                            $friendModel->where('friend_id', $data['receiverId']);
                            $friendModel->update();
                            if($notifyId == -1) {
                                $notify = $notifyModel->where("from_id = {$data['senderId']} and user_id = {$data['receiverId']}")->first();
                                CLI::print(json_encode($notify));
                                if(isset($notify)) {
                                    $notifyId = $notify['id'];
                                }
                            }

                            $notifyModel->update($notifyId, $updateNotify);
                            $newNotifyId =  $notifyModel->insert($newNotify);

                            $updatedNotify = $notifyModel->select('notifications.id, notifications.user_id, type, payload, created_at, seen_at, fullname, from_id')->from('users')->where("user_id = " . $data['receiverId'] . " and user_id = users.id" . " and notifications.id = {$notifyId}")->first();

                            $newedNotify = $notifyModel->select('notifications.id, notifications.user_id, type, payload, created_at, seen_at, fullname, from_id')->from('users')->where("user_id = " . $data['senderId'] . " and user_id = users.id" . " and notifications.id = {$newNotifyId}")->first();

                            $id = $groupMember->select("group_members.group_id")->join("groups", "groups.id = group_members.group_id", 'inner')->where("user_id IN ({$data['senderId']}, {$data['receiverId']}) and type = 'dou'")->groupBy("group_members.group_id")->having("COUNT(DISTINCT user_id) = 2")->orderBy('group_id', 'DESC')->first();

                            if (empty($id)) {
                                $newGroupId = $group->insert([
                                    'name' => '',
                                    'type' => 'dou',
                                ]);

                                $responseData['groupId'] = $newGroupId;

                                $groupMember->insertBatch([[
                                    'group_id' => $newGroupId,
                                    'user_id' => $data['senderId']
                                ], [
                                    'group_id' => $newGroupId,
                                    'user_id' => $data['receiverId']
                                ]]);
                            }

                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onAcceptFriend',
                                        'status' => 'success',
                                        'notify' => $updatedNotify,
                                        'msg' => "Bạn và {$sender['fullname']} đã trở thành bạn bè.",
                                    ]));
                                }

                                if ($client->resourceId == $sender['connectid']) {
                                    $client->send(json_encode([
                                        'event' => 'onAcceptFriend',
                                        'status' => 'success',
                                        'notify' => $newedNotify,
                                        'msg' =>  $receiver['fullname'] . ' đã chấn nhận lời mời kết bạn.',
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "acceptFriend"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'startChatPrivate': {
                        try {
                            $groupMember = new GroupMember();
                            $group = new Group();
                            $messageModel = new Message();
                            $fileMessageModel = new FileMessages();
                            $blockedUserModel = new BlockedUser();
                            $userModel = new User();
                            $sender = $userModel->find($data['senderId']);
                            $receiver = $userModel->find($data['receiverId']);
                            $messages = array();
                            $mediaMessages = array();
                            $files = array();
                            $responseData = [];

                            if (isset($sender) && isset($receiver)) {
                                $id = $groupMember->select("group_members.group_id")->join("groups", "groups.id = group_members.group_id", 'inner')->where("user_id IN ({$data['senderId']}, {$data['receiverId']}) and type = 'dou'")->groupBy("group_members.group_id")->having("COUNT(DISTINCT user_id) = 2")->orderBy('group_id', 'DESC')->first();

                                if (empty($id)) {
                                    $newGroupId = $group->insert([
                                        'name' => '',
                                        'type' => 'dou',
                                    ]);

                                    $responseData['groupId'] = $newGroupId;

                                    $groupMember->insertBatch([[
                                        'group_id' => $newGroupId,
                                        'user_id' => $data['senderId']
                                    ], [
                                        'group_id' => $newGroupId,
                                        'user_id' => $data['receiverId']
                                    ]]);

                                    $messages = $messageModel->where('group_id', $newGroupId)->get()->getResultArray();
                                } else {
                                    $responseData['groupId'] = $id['group_id'];
                                    $messages = $messageModel->select('users.*, messages.*, messages.id as message_id, (SELECT viewed_at FROM viewed_messages WHERE viewed_messages.message_id = messages.id LIMIT 1) as viewed_at')->join('users', 'messages.sender_id = users.id', 'inner')->where("group_id = {$id['group_id']} and (format = 'text' or format = 'call') and messages.id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$data['senderId']})")->get()->getResultArray();

                                    $mediaMessages = $messageModel->select('users.*, messages.*, messages.id as message_id, (SELECT viewed_at FROM viewed_messages WHERE viewed_messages.message_id = messages.id LIMIT 1) as viewed_at, href, name, size')->join('users', 'messages.sender_id = users.id', 'inner')->join('file_messages', 'messages.id = file_messages.message_id', 'inner')->where("group_id = {$id['group_id']} and (format = 'image' or format = 'file') and messages.id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$data['senderId']})")->get()->getResultArray();

                                    $files = $fileMessageModel->select('file_messages.*, format, sent_at, group_id, sender_id')->join('messages', 'file_messages.message_id = messages.id', 'inner')->where('group_id', $responseData['groupId'])->where("message_id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$data['senderId']})")->get()->getResultArray();
                                }

                                $newMediaMessages = [];

                                $block = $blockedUserModel->select('user_id as userId, blocked_user_id as blockedUserId')->where("(user_id = {$data['senderId']} and blocked_user_id ={$data['receiverId']}) or (user_id = {$data['receiverId']} AND blocked_user_id = {$data['senderId']})")->first();

                                foreach ($mediaMessages as $mediaMessage) {
                                    $key = array_search($mediaMessage["message_id"], array_column($newMediaMessages, "message_id"));
                                    if ($key === false) {
                                        $mediaMessage["images"] = [["href" => $mediaMessage["href"], "name" => $mediaMessage["name"], "size" => $mediaMessage["size"]]];
                                        unset($mediaMessage["href"]);
                                        unset($mediaMessage["size"]);
                                        unset($mediaMessage["name"]);
                                        $newMediaMessages[] = $mediaMessage;
                                    } else {
                                        $newMediaMessages[$key]["images"][] = ["href" => $mediaMessage["href"], "name" => $mediaMessage["name"], "size" => $mediaMessage["size"]];
                                    }
                                }

                                $responseData['event'] = 'onStartChatPrivate';
                                $responseData['sender'] = $sender;
                                $responseData['receiver'] = $receiver;
                                $responseData['messages'] = array_merge($messages, $newMediaMessages);
                                $responseData['type'] = "dou";
                                $responseData['files'] = $files;
                                $responseData['blocked'] = $block;

                                foreach ($this->clients as $client) {
                                    if ($from == $client) {
                                        $client->send(json_encode($responseData));
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "startChatPrivate"
                                    ]));
                                }
                            }
                        }
                        break;
                    }


                case 'getConversation': {
                        try {
                            $userId = $data["userId"];
                            if(!isset($userId)) {
                                return;
                            }
                            $groupMember = new GroupMember();
                            $messageModel = new Message();
                            $result = array();
                            $conversation = $groupMember->select("group_members.group_id, `users`.id, receiver.avatar,  `users`.fullname, (SELECT message FROM messages WHERE group_id = group_members.group_id and messages.id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$data['userId']}) ORDER BY sent_at DESC  
                            LIMIT 1) as `message`, m.fullname as sender, m.sender_id, m.sent_at, receiver.fullname as receiverName, receiver.id as receiverId, (SELECT COUNT(*) FROM messages WHERE group_id = group_members.group_id AND messages.id NOT IN ( SELECT message_id FROM deleted_messages WHERE user_id = {$data['userId']} )) AS countMessage,
                            receiver.id in (SELECT blocked_user_id FROM blocked_users WHERE user_id = {$data['userId']} UNION SELECT user_id FROM blocked_users WHERE blocked_user_id = {$data['userId']}) as isBlocked 
                            ")
                                ->join('groups', 'groups.id = group_members.group_id', 'inner')
                                ->join('users', 'users.id = group_members.user_id', 'inner')
                                ->join('messages', 'messages.group_id = groups.id', 'inner')
                                ->join('(SELECT messages.*, users.fullname FROM messages, users WHERE sender_id = users.id ) AS m', 'm.id = last_message', 'LEFT OUTER')
                                ->join("(SELECT u.*, gm.group_id as groupID FROM `groups` g, group_members gm, users u WHERE gm.group_id = g.id and gm.group_id = gm.group_id and type = 'dou' and user_id != {$data['userId']} AND u.id = gm.user_id ) AS receiver", 'receiver.groupID = group_members.group_id', "LEFT OUTER")
                                ->where("`group_members`.user_id = {$data['userId']} and type = 'dou'")
                                ->groupBy([
                                    'group_members.group_id', 'users.id', 'users.fullname', 'm.fullname', 'm.sender_id', 'm.sent_at', 'receiver.fullname', 'receiver.id', 'receiver.avatar'
                                ])
                                ->orderBy('sent_at', 'desc')
                                ->get();

                            $groupConversation = $groupMember->select("`groups`.id as groupId, `groups`.`name` as groupName, (SELECT message FROM messages WHERE group_id = group_members.group_id and messages.id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$data['userId']}) ORDER BY sent_at DESC  
                            LIMIT 1) as `message`, type, `desc`, `groups`.avatar as groupAvatar, m.sent_at, m.sender_id, m.fullname, users.avatar as userAvatar, ( SELECT COUNT(*) FROM messages WHERE group_id = group_members.group_id AND messages.id NOT IN ( SELECT message_id FROM deleted_messages WHERE user_id = {$data['userId']} )) AS countMessage,owner")
                                ->join('groups', 'groups.id = group_members.group_id', 'inner')
                                ->join('users', 'users.id = group_members.user_id', 'inner')
                                ->join('messages', 'messages.group_id = groups.id', 'inner')
                                ->join('(SELECT messages.*, users.fullname FROM messages, users WHERE sender_id = users.id ) AS m', 'm.id = last_message', 'LEFT OUTER')
                                ->where("user_id = {$data['userId']} and `groups`.id = group_members.group_id and type = 'multi'")
                                ->groupBy(['groupId', 'groupName', 'message', 'type', 'desc', 'groupAvatar', 'm.sent_at', 'm.sender_id', 'm.fullname','userAvatar', 'owner'])
                                ->orderBy('sent_at', 'desc')
                                ->get()->getResultArray();

                            foreach ($conversation->getResultArray() as $row) {
                                array_push($result, $row);
                            }

                            $unreadMessages = $messageModel->select('messages.*')->join('group_members', 'messages.group_id = group_members.group_id', 'inner')->where("group_members.user_id = {$data['userId']} And sender_id != {$data['userId']}  AND NOT EXISTS (SELECT * FROM viewed_messages vm WHERE vm.message_id = messages.id and vm.user_id = {$data['userId']})")->get()->getResultArray();

                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onGetConversation',
                                        'conversation' => $result,
                                        'unreadMessages' => $unreadMessages,
                                        'groupConversation' => $groupConversation
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "getConversation"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'sentMessage': {
                        try {
                            $viewedMessage = new ViewedMessage();
                            $fileMessageModel = new FileMessages();
                            $messageModel = new Message();
                            $groupMember = new GroupMember();
                            $groupModel = new Group();
                            $messagesSeen = $data['messages'];
                            $userId = $data['userId'];
                            $groupId = $data['groupId'];
                            $messageIds = array();
                            $updateMessages = array();
                            $connectIds = array();
                            $tempMessageIdsString = "";
                            $messages = array();
                            $mediaMessages = array();
                            $files = array();
                            $newMediaMessages = [];

                            if ($userId == 0 || empty($messagesSeen)) return;
                            $group = $groupModel->find($groupId);

                            if ($messagesSeen == "all") {
                                $messagesSeen = $messageModel->select('id')->where('group_id', $groupId)->get()->getResultArray();
                            }

                            foreach ($messagesSeen as $message) {
                                array_push($updateMessages, [
                                    'message_id' => $message['id'],
                                    'user_id' => $userId,
                                ]);
                                array_push($messageIds, $message['id']);
                            }

                            if (count($updateMessages) > 0) {
                                $viewedMessage->upsertBatch($updateMessages);
                            }


                            $userInGroup = $groupMember->from('users')->where("group_id = {$groupId} AND user_id = users.id and users.id != {$data['userId']}")->get()->getResultArray();


                            foreach ($userInGroup as $user) {
                                if (isset($user['connectid']) && $user['connectid'] != -1) {
                                    array_push($connectIds, (int)$user['connectid']);
                                }
                            }

                            if (count($updateMessages) > 0) {
                                $tempMessageIdsString = implode(', ', $messageIds);
                            } else {
                                $tempMessageIdsString = "0";
                            }

                            // WEB
                            // $messages = $messageModel->select('users.*, messages.*, messages.id as message_id, (SELECT viewed_at FROM viewed_messages WHERE viewed_messages.message_id = messages.id LIMIT 1) as viewed_at')->join('users', 'messages.sender_id = users.id', 'inner')->where("group_id = {$groupId} and messages.id in ({$tempMessageIdsString})")->get()->getResultArray();

                            $messages = $messageModel->select('users.*, messages.*, messages.id as message_id, (SELECT viewed_at FROM viewed_messages WHERE viewed_messages.message_id = messages.id LIMIT 1) as viewed_at')->join('users', 'messages.sender_id = users.id', 'inner')->where("group_id = {$groupId} and format = 'text' and messages.id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$userId})")->get()->getResultArray();

                            if(isset($group) && $group['type'] == 'dou') {
                                $messages = $messageModel->select('users.*, messages.*, messages.id as message_id, (SELECT viewed_at FROM viewed_messages WHERE viewed_messages.message_id = messages.id LIMIT 1) as viewed_at')->join('users', 'messages.sender_id = users.id', 'inner')->where("group_id = {$groupId} and (format = 'text' or format = 'call') and messages.id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$userId})")->get()->getResultArray();

                                $newMediaMessages = $messageModel->select('users.*, messages.*, messages.id as message_id, (SELECT viewed_at FROM viewed_messages WHERE viewed_messages.message_id = messages.id LIMIT 1) as viewed_at, href, name, size')->join('users', 'messages.sender_id = users.id', 'inner')->join('file_messages', 'messages.id = file_messages.message_id', 'inner')->where("group_id = {$groupId} and (format = 'image' or format = 'file') and messages.id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$userId})")->get()->getResultArray();

                                foreach ($mediaMessages as $mediaMessage) {
                                    $key = array_search($mediaMessage["message_id"], array_column($newMediaMessages, "message_id"));
                                    if ($key === false) {
                                        $mediaMessage["images"] = [["href" => $mediaMessage["href"], "name" => $mediaMessage["name"], "size" => $mediaMessage["size"]]];
                                        unset($mediaMessage["href"]);
                                        unset($mediaMessage["size"]);
                                        unset($mediaMessage["name"]);
                                        $newMediaMessages[] = $mediaMessage;
                                    } else {
                                        $newMediaMessages[$key]["images"][] = ["href" => $mediaMessage["href"], "name" => $mediaMessage["name"], "size" => $mediaMessage["size"]];
                                    }
                                }
                            }

                            if (isset($group) && $group['type'] == 'multi') {
                                $messages = $messageModel->select("`messages`.*, fullname")->from("users")
                                ->where("group_id = {$groupId} and `users`.id = sender_id and (format = 'text' or format = 'call') and messages.id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$userId})")->get()->getResultArray();

                                $mediaMessages = $messageModel->select('users.*, messages.*, messages.id as message_id, (SELECT viewed_at FROM viewed_messages WHERE viewed_messages.message_id = messages.id LIMIT 1) as viewed_at, href, name, size')->join('users', 'messages.sender_id = users.id', 'inner')->join('file_messages', 'messages.id = file_messages.message_id', 'inner')->where("group_id = {$groupId} and (format = 'image' or format = 'file') and messages.id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$userId})")->get()->getResultArray();

                                foreach ($mediaMessages as $mediaMessage) {
                                    $key = array_search($mediaMessage["message_id"], array_column($newMediaMessages, "message_id"));
                                    if ($key === false) {
                                        $mediaMessage["images"] = [["href" => $mediaMessage["href"], "name" => $mediaMessage["name"], "size" => $mediaMessage["size"]]];
                                        unset($mediaMessage["href"]);
                                        unset($mediaMessage["size"]);
                                        unset($mediaMessage["name"]);
                                        $newMediaMessages[] = $mediaMessage;
                                    } else {
                                        $newMediaMessages[$key]["images"][] = ["href" => $mediaMessage["href"], "name" => $mediaMessage["name"], "size" => $mediaMessage["size"]];
                                    }
                                }
                            }


                            foreach ($this->clients as $client) {
                                if (in_array($client->resourceId, $connectIds)) {
                                    $client->send(json_encode([
                                        'event' => 'onResponseSent',
                                        'messages' => array_merge($messages, $newMediaMessages),
                                        'groupId' => $groupId,
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "sentMessage"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'createGroup': {
                        try {
                            $group = new Group();
                            $groupMember = new GroupMember();
                            $message = new Message();
                            $newMemberOfGroup = array();
                            $connectIds = array();
                            $fileTypes = ['image/jpeg', 'image/png', 'image/gif'];
                            $href = null;

                            if (isset($data['avatar'])) {
                                list($type, $file) = explode(';', $data['avatar']['data']);
                                list(, $file) = explode(',', $file);
                                $file = base64_decode($file);
                                file_put_contents($data['avatar']['name'], $file);
                                $file_url = realpath($data['avatar']['name']);

                                $uploader = new Uploader();
                                $result = $uploader->uploadPath($file_url);

                                if (in_array($data['avatar']['type'], $fileTypes) && isset($result)) {
                                    $href = $result['secure_url'];
                                }

                                if (unlink($file_url)) {
                                    CLI::print('File đã được xóa.');
                                } else {
                                    CLI::print('Không thể xóa file.');
                                }
                            }

                            $newGroupId = $group->insert([
                                'name' => $data['groupName'],
                                'desc' => $data['groupDesc'],
                                'owner' => $data['senderId'],
                                'type' => 'multi',
                                'avatar' => $href
                            ]);

                            foreach ($data['groupMember[]'] as $member) {
                                array_push($newMemberOfGroup, [
                                    'group_id' => $newGroupId,
                                    'user_id' => $member,
                                ]);
                            }

                            array_push($newMemberOfGroup, [
                                'group_id' => $newGroupId,
                                'user_id' => $data['senderId'],
                            ]);

                            $systemMessageId = $message->insert([
                                'sender_id' => 0,
                                'message' => "{$data['fullname']} đã tạo nhóm",
                                'group_id' => $newGroupId,
                            ]);

                            $groupMember->insertBatch($newMemberOfGroup);

                            $userInGroup = $groupMember->from('users')->where("group_id", $newGroupId)->get()->getResultArray();


                            foreach ($userInGroup as $user) {
                                if (isset($user['connectid']) && $user['connectid'] != -1) {
                                    array_push($connectIds, (int)$user['connectid']);
                                }
                            }

                            foreach ($this->clients as $client) {
                                if (in_array($client->resourceId, $connectIds)) {
                                    $client->send(json_encode([
                                        'event' => 'onCreateChatGroup',
                                        'userCreateId' => $data['senderId'],
                                        'userCreateName' => $data['fullname'],
                                        'groupName' => $data['groupName'],
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "createGroup"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'getMembersOfGroup': {
                    try {
                        $groupMemberModel = new GroupMember();
                        $userId = $data['userId'];
                        $groupId = $data['groupId'];
                        $members = $groupMemberModel->select("users.*, ( SELECT `status` FROM friends WHERE (friends.user_id = {$userId} AND users.id = friends.friend_id) OR (friends.friend_id = {$userId} AND friends.user_id = users.id) LIMIT 1) AS `status`, (SELECT user_id FROM friends
                WHERE (user_id = {$userId} OR friend_id = {$userId}) AND (users.id = friend_id OR user_id = users.id) LIMIT 1) AS user_id,
                (SELECT friend_id FROM friends
                WHERE (user_id = {$userId} OR friend_id = {$userId}) AND (users.id = friend_id OR user_id = users.id) LIMIT 1) AS friend_id, (
            select user_id
            FROM blocked_users
            where (user_id = {$userId} and blocked_user_id = id) or (user_id = id AND blocked_user_id = {$userId}) LIMIT 1) as blockBy, (
            select blocked_user_id
            FROM blocked_users
            where (user_id = {$userId} and blocked_user_id = id) or (user_id = id AND blocked_user_id = {$userId}) LIMIT 1) as blocked_user_id")->from('users')->where('group_id', $groupId)->where('user_id = users.id')->get()->getResultArray();

                        foreach ($this->clients as $client) {
                            if ($from == $client) {
                                $client->send(json_encode(
                                        [
                                            'event' => 'onGetMembersOfGroup',
                                            'members' => $members
                                        ]
                                ));
                            }
                        }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "getMembersOfGroup"
                                    ]));
                                }
                            }
                        }
                    break;
                }

                case 'startChatMulti': {
                        try {
                            $messageModel = new Message();
                            $groupModel = new Group();
                            $userId = $data['userId'];
                            $groupId = $data['groupId'];
                            $fileMessageModel = new FileMessages();
                            $groupMemberModel = new GroupMember();

                            $messageOfConversation = $messageModel->select("`messages`.*, fullname")->from("users")
                                ->where("group_id = {$groupId} and `users`.id = sender_id and (format = 'text' or format = 'call') and messages.id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$userId})")->get()->getResultArray();

                            $groupInfo = $groupModel->find($groupId);

                            // $files = $fileMessageModel->select('file_messages.*, format, sent_at, group_id, sender_id')->join('messages', 'file_messages.message_id = messages.id', 'inner')->where('group_id', $groupId)->get()->getResultArray();

                            $mediaMessages = $messageModel->select('users.*, messages.*, messages.id as message_id, (SELECT viewed_at FROM viewed_messages WHERE viewed_messages.message_id = messages.id LIMIT 1) as viewed_at, href, name, size')->join('users', 'messages.sender_id = users.id', 'inner')->join('file_messages', 'messages.id = file_messages.message_id', 'inner')->where("group_id = {$groupId} and (format = 'image' or format = 'file') and messages.id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$userId})")->get()->getResultArray();


                            $files = $fileMessageModel->select('file_messages.*, format, sent_at, group_id, sender_id')->join('messages', 'file_messages.message_id = messages.id', 'inner')->where('group_id', $groupId)->where("message_id not in (SELECT message_id FROM deleted_messages WHERE user_id = {$userId})")->get()->getResultArray();

                            $members = $groupMemberModel->select("users.*, ( SELECT `status` FROM friends WHERE (friends.user_id = {$userId} AND users.id = friends.friend_id) OR (friends.friend_id = {$userId} AND friends.user_id = users.id) LIMIT 1) AS `status`, (SELECT user_id FROM friends
                WHERE (user_id = {$userId} OR friend_id = {$userId}) AND (users.id = friend_id OR user_id = users.id) LIMIT 1) AS user_id,
                (SELECT friend_id FROM friends
                WHERE (user_id = {$userId} OR friend_id = {$userId}) AND (users.id = friend_id OR user_id = users.id) LIMIT 1) AS friend_id, (
            select user_id
            FROM blocked_users
            where (user_id = {$userId} and blocked_user_id = id) or (user_id = id AND blocked_user_id = {$userId}) LIMIT 1) as blockBy, (
            select blocked_user_id
            FROM blocked_users
            where (user_id = {$userId} and blocked_user_id = id) or (user_id = id AND blocked_user_id = {$userId}) LIMIT 1) as blocked_user_id")->from('users')->where('group_id', $groupId)->where('user_id = users.id')->get()->getResultArray();

                            $groupInfo['members'] = $members;

                            $newMediaMessages = [];

                            foreach ($mediaMessages as $mediaMessage) {
                                $key = array_search($mediaMessage["message_id"], array_column($newMediaMessages, "message_id"));
                                if ($key === false) {
                                    $mediaMessage["images"] = [["href" => $mediaMessage["href"], "name" => $mediaMessage["name"], "size" => $mediaMessage["size"]]];
                                    unset($mediaMessage["href"]);
                                    unset($mediaMessage["size"]);
                                    unset($mediaMessage["name"]);
                                    $newMediaMessages[] = $mediaMessage;
                                } else {
                                    $newMediaMessages[$key]["images"][] = ["href" => $mediaMessage["href"], "name" => $mediaMessage["name"], "size" => $mediaMessage["size"]];
                                }
                            }

                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onStartChatMulti',
                                        'userId' => $userId,
                                        'groupInfo' => $groupInfo,
                                        'files' => $files,
                                        'messages' => array_merge($messageOfConversation, $newMediaMessages),
                                        'type' => 'multi'
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "startChatMulti"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'sendFile': {
                        try {
                            $messageModel = new Message();
                            $fileMessageModel = new FileMessages();
                            $userModel = new User();
                            $groupModel = new Group();
                            $groupMemberModel = new GroupMember();
                            $images = array();
                            $connectIds = array();
                            $others = array();
                            $fileTypes = ['image/jpeg', 'image/png', 'image/gif'];

                            $sender = $userModel->find($data['senderId']);
                            $group = $groupModel->find($data['groupId']);
                            foreach ($data['files'] as $fileUpload) {

                                list($type, $file) = explode(';', $fileUpload['data']);
                                list(, $file) = explode(',', $file);
                                $file = base64_decode($file);
                                file_put_contents($fileUpload['name'], $file);
                                $file_url = realpath($fileUpload['name']);

                                $uploader = new Uploader();
                                $result = $uploader->uploadPath($file_url);

                                CLI::print(json_encode($result));

                                if (in_array($fileUpload['type'], $fileTypes) && isset($result)) {
                                    $nameFile = $result['original_filename'] . "." . $result['format'];
                                    $sizeFile = $result['bytes'];
                                    $href = $result['secure_url'];
                                    array_push($images, ['name' => $nameFile, 'size' => $sizeFile, 'href' => $href]);
                                }


                                if (!in_array($fileUpload['type'], $fileTypes) && isset($result)) {
                                    $extension = pathinfo($result['public_id'], PATHINFO_EXTENSION);
                                    if (isset($result['format'])) {
                                        $extension = $result['format'];
                                    }

                                    $nameFile = $result['original_filename'] . "." . $extension;
                                    $sizeFile = $result['bytes'];
                                    $href = $result['secure_url'];
                                    array_push($others, ['name' => $nameFile, 'size' => $sizeFile, 'href' => $href]);
                                }

                                if (unlink($file_url)) {
                                    CLI::print('File đã được xóa.');
                                } else {
                                    CLI::print('Không thể xóa file.');
                                }
                            }

                            if (count($images) > 0) {
                                $messageId = $messageModel->insert([
                                    'sender_id' => $data['senderId'],
                                    'message' => "[Hình ảnh]",
                                    'group_id' => $data['groupId'],
                                    'format' => "image",
                                ]);
                                $pushImageBatch = array();
                                foreach ($images as $image) {
                                    array_push($pushImageBatch, ['name' => $image['name'], 'size' => $image['size'], 'href' => $image['href'], 'message_id' => $messageId]);
                                }
                                $fileMessageModel->insertBatch($pushImageBatch);
                            }

                            foreach ($others as $other) {
                                $messageId = $messageModel->insert([
                                    'sender_id' => $data['senderId'],
                                    'message' => "[File]",
                                    'group_id' => $data['groupId'],
                                    'format' => "file",
                                ]);

                                $fileMessageModel->insert(['name' => $other['name'], 'size' => $other['size'], 'href' => $other['href'], 'message_id' => $messageId]);
                            }

                            $listUserReceiver = $userModel->select('connectid')->where("id IN (SELECT user_id FROM `groups` JOIN group_members on group_id = `groups`.id WHERE `groups`.id = {$data['groupId']})")->get()->getResultArray();

                            foreach ($listUserReceiver as $user) {
                                if (isset($user))
                                    array_push($connectIds, $user['connectid']);
                            }

                            if (isset($group) && $group['type'] == 'dou') {
                                $userInGroup = $groupMemberModel->select('user_id')->where("group_id = {$data['groupId']} AND user_id != {$data['senderId']}")->first();
                                $data['groupId'] = $userInGroup['user_id'];
                            }

                            if (isset($group) && $group['type'] == 'multi') {
                                $data['groupId'] = $group['id'];
                            }

                            foreach ($this->clients as $client) {
                                if (in_array($client->resourceId, $connectIds) || $from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onNewMessage',
                                        'msg' => 'Đã gửi một tệp tin',
                                        'senderId' => $data['senderId'],
                                        'groupName' => $group['name'],
                                        'from' => $sender['fullname'],
                                        'groupId' => $data['groupId'],
                                        'type' => $group['type'],
                                    ]));
                                } else {
                                    // $private_chat_object->setStatus('No');
                                    // $private_chat_object->setChatMessageId($chat_message_id);

                                    // $private_chat_object->update_chat_status();
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "sendFile"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'getOnline': {
                        try {
                            $userModel = new User();
                            $friendModel = new Friend();

                            $friends = $friendModel->select('friends.user_id, friends.friend_id, users.*, friends.status')->join('users', "(friends.user_id = users.id and friends.user_id != {$data['id']}) or (friends.friend_id = users.id and friends.friend_id != {$data['id']})")->where(" (friend_id = {$data['id']} or user_id = {$data['id']} ) and `status` = 'accepted' and id not in (SELECT blocked_user_id FROM blocked_users WHERE user_id = {$data['id']}) and id not in (SELECT user_id FROM blocked_users WHERE blocked_user_id = {$data['id']})")->findAll();

                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onGetOnline',
                                        'friends' => $friends,
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "getOnline"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'forwardMessage': {
                        try {
                            $receiversPrivate = $data['receiversPrivate'];
                            $receiversGroup = $data['receiversGroup'];
                            $senderId = $data['senderId'];
                            $messageId = $data['messageId'];
                            $messageModel = new Message();
                            $userModel = new User();
                            $groupMember = new GroupMember();
                            $groupModel = new Group();
                            $fileMessageModel = new FileMessages();
                            $connectIds = array();
                            $messageForward = array();

                            $message = $messageModel->find($messageId);
                            $sender = $userModel->find($senderId);

                            if (!isset($message)) {
                                foreach ($this->clients as $client) {
                                    if ($from == $client) {
                                        $client->send(json_encode([
                                            'event' => 'onForwardMessage',
                                            'result' => false,
                                        ]));
                                    }
                                }
                                return;
                            }

                            if ($message['format'] == 'text') {
                                $messageForward = [
                                    'sender_id' => $senderId,
                                    'message' => $message['message'],
                                ];
                            }

                            if ($message['format'] == 'image' || $message['format'] == 'file') {
                                $messageForward = [
                                    'sender_id' => $senderId,
                                    'message' => $message['message'],
                                    'format' => $message['format'],
                                ];
                            }

                            foreach ($receiversPrivate as $receiver) {
                                $id = $groupMember->select("group_members.group_id")->join("groups", "groups.id = group_members.group_id", 'inner')->where("user_id IN ({$senderId}, {$receiver}) and type = 'dou'")->groupBy("group_members.group_id")->having("COUNT(DISTINCT user_id) = 2")->orderBy('group_id', 'DESC')->first();

                                if (empty($id)) {
                                    $newGroupId = $groupModel->insert([
                                        'name' => '',
                                        'type' => 'dou',
                                    ]);

                                    $responseData['groupId'] = $newGroupId;

                                    $groupMember->insertBatch([[
                                        'group_id' => $newGroupId,
                                        'user_id' => $senderId
                                    ], [
                                        'group_id' => $newGroupId,
                                        'user_id' => $receiver
                                    ]]);

                                    $messageForward['group_id'] = $newGroupId;
                                } else {
                                    $messageForward['group_id'] = $id['group_id'];
                                }

                                $forwardMessageId = $messageModel->insert($messageForward);
                                if ($message['format'] == 'image' || $message['format'] == 'file') {
                                    $files = $fileMessageModel->where('message_id', $messageId)->get()->getResultArray();
                                    $forwardMessageFile = array();
                                    foreach ($files as $file) {
                                        array_push(
                                            $forwardMessageFile,
                                            [
                                                'name' => $file['name'],
                                                'size' => $file['size'],
                                                'href' => $file['href'],
                                                'message_id' => $forwardMessageId
                                            ]
                                        );
                                    }

                                    $fileMessageModel->insertBatch($forwardMessageFile);
                                }

                                $userReceiver = $userModel->find($receiver);




                                $group = $groupModel->find($messageForward['group_id']);
                                if (isset($group) && $group['type'] == 'dou') {
                                    $userInGroup = $groupMember->select('user_id')->where("group_id = {$messageForward['group_id']} AND user_id != {$data['senderId']}")->first();
                                    $data['groupId'] = $userInGroup['user_id'];
                                }

                                if (isset($group) && $group['type'] == 'multi') {
                                    $data['groupId'] = $group['id'];
                                }

                                foreach ($this->clients as $client) {
                                    if ($client->resourceId == (int)$userReceiver['connectid'] || $client == $from) {
                                        $client->send(json_encode([
                                            'event' => 'onNewMessage',
                                            'groupName' => $group['name'],
                                            'senderId' => $senderId,
                                            'from' => $sender['fullname'],
                                            'msg' => 'Vừa chuyển tiếp một tin nhắn cho bạn',
                                            'groupId' => $data['groupId'],
                                            'type' => $group['type'],
                                            'result' => true,
                                        ]));
                                    }
                                }
                            }

                            foreach ($receiversGroup as $receiver) {
                                $messageForward['group_id'] = $receiver;
                                $forwardMessageId = $messageModel->insert($messageForward);

                                if ($message['format'] == 'image' || $message['format'] == 'file') {
                                    $files = $fileMessageModel->where('message_id', $messageId)->get()->getResultArray();
                                    $forwardMessageFile = array();
                                    foreach ($files as $file) {
                                        array_push(
                                            $forwardMessageFile,
                                            [
                                                'name' => $file['name'],
                                                'size' => $file['size'],
                                                'href' => $file['href'],
                                                'message_id' => $forwardMessageId
                                            ]
                                        );
                                    }

                                    $fileMessageModel->insertBatch($forwardMessageFile);
                                }

                                $listUserReceiver = $userModel->select('connectid')->where("id IN (SELECT user_id FROM `groups` JOIN group_members on group_id = `groups`.id WHERE `groups`.id = {$receiver})")->get()->getResultArray();

                                foreach ($listUserReceiver as $user) {
                                    if (isset($user))
                                        array_push($connectIds, $user['connectid']);
                                }

                                $group = $groupModel->find($messageForward['group_id']);
                                if (isset($group) && $group['type'] == 'dou') {
                                    $userInGroup = $groupMember->select('user_id')->where("group_id = {$messageForward['group_id']} AND user_id != {$data['senderId']}")->first();
                                    $data['groupId'] = $userInGroup['user_id'];
                                }

                                if (isset($group) && $group['type'] == 'multi') {
                                    $data['groupId'] = $group['id'];
                                }

                                foreach ($this->clients as $client) {
                                    if (in_array($client->resourceId, $connectIds)) {
                                        $client->send(json_encode([
                                            'event' => 'onNewMessage',
                                            'senderId' => $senderId,
                                            'groupName' => $group['name'],
                                            'type' => $group['type'],
                                            'from' => $sender['fullname'],
                                            'msg' => 'Vừa chuyển tiếp một tin nhắn cho bạn',
                                            'groupId' => $data['groupId'],
                                            'result' => true,
                                        ]));
                                    }
                                }
                            }

                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onForwardMessage',
                                        'result' => true,
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "forwardMessage"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'deleteMessage': {
                        try {
                            $userId = $data['userId'];
                            $messageId = $data['messageId'];
                            $type = $data['type'];
                            $groupId = $data['groupId'];
                            $deletedMessageModel = new DeletedMessage();
                            $groupMemberModel = new GroupMember();
                            $messageModel = new Message();
                            $connectIds = array();
                            $userModel = new User();

                            if ($type == 'all') {
                                $groupMembers = $groupMemberModel->where('group_id', $groupId)->get()->getResultArray();

                                foreach ($groupMembers as $groupMember) {
                                    $deletedMessageModel->upsertBatch([
                                        'message_id' => $messageId,
                                        'user_id' => $groupMember['user_id'],
                                    ]);
                                }
                            } else {
                                $deletedMessageModel->upsertBatch([
                                    'message_id' => $messageId,
                                    'user_id' => $userId,
                                ]);
                            }

                            $listUserReceiver = $userModel->select('connectid')->where("id IN (SELECT user_id FROM `groups` JOIN group_members on group_id = `groups`.id WHERE `groups`.id = {$groupId})")->get()->getResultArray();

                            foreach ($listUserReceiver as $user) {
                                if (isset($user))
                                    array_push($connectIds, $user['connectid']);
                            }
                            foreach ($this->clients as $client) {
                                if (in_array($client->resourceId, $connectIds)) {
                                    $client->send(json_encode([
                                        'event' => 'onDeleteMessage',
                                        'messageId' => $messageId,
                                        'groupId' => $groupId,
                                        'userId' => $userId,
                                        'type' => $type,
                                        'result' => true,
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "deleteMessage"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'deleteConversation': {
                        try {
                            $groupId = $data['groupId'];
                            $userId = $data['userId'];
                            $messageModel = new Message();
                            $deletedMessageModel = new DeletedMessage();

                            $messageDelete = array();

                            $messages = $messageModel->where('group_id', $groupId)->get()->getResultArray();

                            foreach ($messages as $message) {
                                array_push($messageDelete, [
                                    'message_id' => $message['id'],
                                    'user_id' => $userId
                                ]);
                            }

                            if (count($messageDelete) > 0) {
                                $result = $deletedMessageModel->upsertBatch($messageDelete);
                                foreach ($this->clients as $client) {
                                    if ($from == $client) {
                                        $client->send(json_encode([
                                            'event' => 'onDeleteConversation',
                                            'result' => $result,
                                        ]));
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "deleteConversation"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'outGroup': {
                        try {
                            $groupId = $data['groupId'];
                            $userId = $data['userId'];
                            $groupMemberModel = new GroupMember();
                            $messageModel = new Message();
                            $userModel = new User();
                            $messageDelete = array();
                            $connectIds = array();


                            $listUserReceiver = $userModel->select('connectid')->where("id IN (SELECT user_id FROM `groups` JOIN group_members on group_id = `groups`.id WHERE `groups`.id = {$groupId})")->get()->getResultArray();

                            $groupMemberModel->where('group_id', $groupId)->where('user_id', $userId);
                            $result = $groupMemberModel->delete();

                            $user = $userModel->find($userId);

                            $systemMessageId = $messageModel->insert([
                                'sender_id' => 0,
                                'message' => "{$user['fullname']} đã rời khỏi nhóm",
                                'group_id' => $groupId,
                            ]);

                            foreach ($listUserReceiver as $user) {
                                if (isset($user))
                                    array_push($connectIds, $user['connectid']);
                            }

                            foreach ($this->clients as $client) {
                                if (in_array($client->resourceId, $connectIds) || $from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onSomeoneExitGroup',
                                        'groupId' => $groupId,
                                        'userId' => $userId
                                    ]));
                                }

                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onOutGroup',
                                        'result' => $result,
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "outGroup"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'addMemberToGroup': {
                        try {
                            $groupMemberModel = new GroupMember();
                            $members = $data['members'];
                            $groupId = $data['groupId'];
                            $userModel = new User();
                            $groupModel = new Group();
                            $messageModel = new Message();
                            $messageDelete = array();
                            $connectIds = array();


                            $membersInsert = array();

                            foreach ($members as $member) {
                                array_push($membersInsert, [
                                    'user_id' => $member,
                                    'group_id' => $groupId
                                ]);
                            }

                            $stringListMember = implode(",", $members);
                            $users = $userModel->select('fullname')->where("id IN ({$stringListMember})")->get()->getResultArray();

                            $stringFullnames = implode(', ', array_map(function ($user) {
                                return $user['fullname'];
                            }, $users));

                            $systemMessageId = $messageModel->insert([
                                'sender_id' => 0,
                                'message' => "{$stringFullnames} đã được thêm vào nhóm",
                                'group_id' => $groupId,
                            ]);


                            $groupMemberModel->upsertBatch($membersInsert);

                            $listUserReceiver = $userModel->select('connectid')->where("id IN (SELECT user_id FROM `groups` JOIN group_members on group_id = `groups`.id WHERE `groups`.id = {$groupId})")->get()->getResultArray();


                            foreach ($listUserReceiver as $user) {
                                if (isset($user))
                                    array_push($connectIds, $user['connectid']);
                            }

                            foreach ($this->clients as $client) {
                                if (in_array($client->resourceId, $connectIds)) {
                                    $client->send(json_encode([
                                        'event' => 'onAddMemberToGroup',
                                        'members' => $members,
                                        'groupId'  => $groupId
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "addMemberToGroup"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'deleteGroup': {
                        try {
                            $groupId = $data['groupId'];
                            $ownerId = $data['ownerId'];
                            $groupModel = new Group();
                            $userModel = new User();
                            $connectIds = array();

                            $listUserReceiver = $userModel->select('connectid')->where("id IN (SELECT user_id FROM `groups` JOIN group_members on group_id = `groups`.id WHERE `groups`.id = {$groupId})")->get()->getResultArray();


                            foreach ($listUserReceiver as $user) {
                                if (isset($user))
                                    array_push($connectIds, $user['connectid']);
                            }

                            $group = $groupModel->find($groupId);
                            if (isset($group) && $group['owner'] == $ownerId) {
                                $groupModel->delete($groupId);

                                foreach ($this->clients as $client) {
                                    if (in_array($client->resourceId, $connectIds)) {
                                        $client->send(json_encode([
                                            'event' => 'onDeleteGroup',
                                            'groupId'  => $groupId
                                        ]));
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "deleteGroup"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                case 'unfriend': {
                        try {
                            $userId = $data['userId'];
                            $friendId = $data['friendId'];
                            $userModel = new User();
                            $friendModel = new Friend();

                            $friendModel->where("(user_id = {$userId} and friend_id = {$friendId}) OR (friend_id = {$userId} AND user_id = {$friendId})");

                            $result = $friendModel->delete();

                            $friend = $userModel->find($friendId);

                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onUnfriend',
                                    ]));
                                }

                                if (isset($friend) && $client->resourceId == $friend['connectid']) {
                                    $client->send(json_encode([
                                        'event' => 'onUnfriend',
                                    ]));
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($this->clients as $client) {
                                if ($from == $client) {
                                    $client->send(json_encode([
                                        'event' => 'onError',
                                        'e' => json_encode($e->getMessage()),
                                        'case' => "unfriend"
                                    ]));
                                }
                            }
                        }
                        break;
                    }

                    // case 'close': {
                    //         $this->clients->detach($from);
                    //         $userModel = new User();

                    //         $querystring = $from->httpRequest->getUri()->getQuery();

                    //         parse_str($querystring, $queryarray);

                    //         $userModel->update($queryarray['id'], ['connectid' => -1]);
                    //         break;
                    //     }


                default:
                    # code...
                    break;
            }
        } catch (\Throwable $th) {
            throw new \Exception($th);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        $userModel = new User();

        $querystring = $conn->httpRequest->getUri()->getQuery();

        parse_str($querystring, $queryarray);

        $userModel->update($queryarray['id'], ['connectid' => -1]);

        CLI::print("Connection {$conn->resourceId} has disconnected\n");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        CLI::print("An error has occurred: {$e->getMessage()}\n");

        $conn->close();
    }
}
