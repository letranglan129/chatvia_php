<?php

namespace App\Controllers;

use App\Libraries\Session;
use App\Libraries\Token;
use App\Libraries\Uploader;
use App\Models\Notification;
use Config\Services;
use App\Models\User as UserModel;
use CodeIgniter\CLI\CLI;

class User extends BaseController
{
    public function search()
    {
        try {
            $userModel = new UserModel();
            $q = $this->request->getVar('q');
            $qId = $this->request->getVar('id');

            $token = $this->request->getCookie("token");
            $user = Token::decode($token);
            $id = $user->id;
            
            if (isset($q, $id) && trim($q) != '') {
                $userResult = $userModel->select("id, email, fullname, phone, connectId, avatar, ( SELECT `status` FROM friends WHERE (user_id = {$id} OR friend_id = {$id}) AND (users.id = friend_id OR user_id = users.id) LIMIT 1) AS `status`, (SELECT user_id FROM friends
                WHERE (user_id = {$id} OR friend_id = {$id}) AND (users.id = friend_id OR user_id = users.id) LIMIT 1) AS user_id,
                (SELECT friend_id FROM friends
                WHERE (user_id = {$id} OR friend_id = {$id}) AND (users.id = friend_id OR user_id = users.id) LIMIT 1) AS friend_id, (
            select user_id
            FROM blocked_users
            where (user_id = {$id} and blocked_user_id = id) or (user_id = id AND blocked_user_id = {$id}) LIMIT 1) as blockBy, (
            select blocked_user_id
            FROM blocked_users
            where (user_id = {$id} and blocked_user_id = id) or (user_id = id AND blocked_user_id = {$id}) LIMIT 1) as blocked_user_id")->where('id' != $id)
                    ->where("fullname LIKE '%{$q}%' ESCAPE '!' or email LIKE '%{$q}%' ESCAPE '!' or phone LIKE '%{$q}%' ESCAPE '!'")->get()->getResultArray();
                return json_encode($userResult);
            }

            if (isset($qId, $id)) {
                $userModel = new UserModel();

                $user = $userModel->find($qId);

                if (isset($user) && $user['id'] != '0') {
                    return json_encode($user);
                }
            }

            return json_encode(null);
        } catch (\Throwable $th) {
            throw new \Exception($th);
        }
    }

    public function update()
    {
        $userModel = new UserModel();
        $email = $this->request->getVar('email');
        $fullname = $this->request->getVar('fullname');
        $phone = $this->request->getVar('phone');
        $describe = $this->request->getVar('describe');

        if (isset($email, $fullname) && $email != "" && $fullname != "") {

            $userModel->set([
                'email' => $email,
                'fullname' => $fullname,
                'phone' => $phone,
                'describe' => $describe
            ]);
            $userModel->where('email', $email);
            $result = $userModel->update();
            $user = $userModel->where('email', $email)->first();
            Session::data('user', $user);
            return json_encode([
                'email' => $email,
                'fullname' => $fullname,
                'phone' => $phone,
                'describe' => $describe,
                'result' => $result
            ]);
        } else {
            return json_encode(null);
        }
    }

    public function updateAvatar()
    {
        try {
            $userModel = new UserModel();

            $token = $this->request->getCookie("token");
            $user = Token::decode($token);
            $id = $user->id;

            if (isset($_FILES['update-avatar'])  && $id != "") {
                $fileName = $_FILES['update-avatar']['name'];
                $fileSize = $_FILES['update-avatar']['size'];
                $fileType = $_FILES['update-avatar']['type'];
                $fileError = $_FILES['update-avatar']['error'];
                $fileTmp = $_FILES['update-avatar']['tmp_name'];

                // Move the file to the upload directory
                $uploader = new Uploader();

                $result = $uploader->uploadImage($fileTmp, $fileName);
                $userModel->update($id, ['avatar' => $result]);
                $user = $userModel->find($id);
                Session::data('user', $user);
                return json_encode([    
                    'result' => $result
                ]);
            }

            return json_encode([
                'result' => null
            ]);
        } catch (\Throwable $th) {
            return json_encode([
                'result' =>  new \Exception($th)
            ]);
        }
    }

    public function changePassword()
    {
        $userModel = new UserModel();
        
        $token = $this->request->getCookie("token");
        $user = Token::decode($token);
        $id = $user->id;

        $email = $this->request->getVar('email');
        $oldPassword = $this->request->getVar('oldPassword');
        $newPassword = $this->request->getVar('newPassword');
        $reNewPassword = $this->request->getVar('reNewPassword');

        if (!isset($email)) {
            return json_encode([
                'result' => false,
                'message' => 'Đã xảy ra lỗi!!!',
            ]);
        }

        $user = $userModel->where('email', $email)->first();

        if (!isset($user)) {
            return json_encode([
                'result' => false,
                'message' => 'Người dùng không tồn tại',
            ]);
        }

        if (isset($user) && $newPassword !== $reNewPassword) {
            return json_encode([
                'result' => false,
                'message' => 'Nhập lại mật khẩu không trùng khớp',
            ]);
        }

        if (!password_verify($oldPassword, $user['password'])) {
            return json_encode([
                'result' => false,
                'message' => 'Mật khẩu cũ không chính xác',
            ]);
        }

        $userModel->set([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);
        $userModel->where('email', $email);
        $result = $userModel->update();

        if($result == true)
            return json_encode([
                'result' => true,
                'message' => 'Đổi mật khẩu thành công',
            ]);
        else
            return json_encode([
                'result' => false,
                'message' => 'Đã xảy ra lỗi!!!',
            ]);
    }

    public function registerView()
    {
        return view('register');
    }

    public function loginView()
    {
        return view('login');
    }

    public function register()
    {
        $email = $this->request->getVar('email');
        $fullname = $this->request->getVar('fullname');
        $password = $this->request->getVar('password');
        $repassword = $this->request->getVar('repassword');

        Session::data("email", $email);
        Session::data("fullname", $fullname);
        if ($password != $repassword) {
            Session::data("passwordError", "Mật khẩu không khớp!!!");
            return view('register');
        } else {
            $userModel = new UserModel();

            $user = $userModel->where('email', $email)->first();

            if ($user) {
                Session::data("emailError", "Email đã tồn tại!!!");
                return view('register');
            } else {

                $user = [
                    'email' => $email,
                    'fullname' => $fullname,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ];

                $userModel->insert($user);

                Session::data("msg_reg_sucesss", "Đăng ký tài khoản thành công!!!");
                return redirect()->to(base_url('/login'));
            }
        }
    }

    public function login()
    {
        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            Session::data('user', $user);

            session()->set([
                'isLoggedIn' => true,
            ]);
            
            $this->response->setCookie("token", Token::encode($user), "", '', '/', '', false, false   , null);

            return redirect()->to(base_url('/'))->withCookies();
        } else {
            Session::data('wrong_info', 'Thông tin đăng nhập không chính xác!!!');
            return view('login');
        }
    }

    public function logout()
    {
        session()->destroy();
        $this->response->setCookie('token');
        Session::delete();
        return redirect()->to(base_url('/'))->withCookies();
    }

    public function signIn()
    {
        try {
            $email = $this->request->getVar('email');
            $password = $this->request->getVar('password');

            $userModel = new UserModel();
            $user = $userModel->where('email', $email)->first();

            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']);

                session()->set([
                    'isLoggedIn' => true,
                ]);

                $token = Token::encode($user);
                $user["token"] = $token;
                return json_encode($user);
            } else {
                $this->response->setStatusCode(400);
                return json_encode(null);
            }
        } catch (\Throwable $th) {
            $this->response->setStatusCode(400);
            return json_encode(null);
        }
    }

    public function signUp()
    {
        try {
            $email = $this->request->getVar('email');
            $fullname = $this->request->getVar('fullname');
            $password = $this->request->getVar('password');
            $repassword = $this->request->getVar('repassword');

            if ($password != $repassword) {
                return json_encode([
                    'msg' => "Mật khẩu nhập lại không khớp!!!",
                    'result' => false,
                ]);
            } else {
                $userModel = new UserModel();

                $user = $userModel->where('email', $email)->first();

                if ($user) {
                    return json_encode([
                        'msg' => "Email đã tồn tại!!!",
                        'result' => false,
                    ]);
                } else {

                    $user = [
                        'email' => $email,
                        'fullname' => $fullname,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                    ];

                    $userModel->insert($user);

                    return json_encode([
                        'msg' => "Đăng ký tài khoản thành công!!!",
                        'result' => true,
                    ]);
                }
            }
        } catch (\Throwable $th) {
            $this->response->setStatusCode(400);
            return json_encode(null);
        }
    }

    public function test()
    {
        $token = $this->request->getCookie("token");
        $user = Token::decode($token);
        $id = $user->id;
        return json_encode($id);
    }

    public function updateAvatarWithBase64()
    {
        try {
            $userModel = new UserModel();

            $token = $this->request->getCookie("token");
            $user = Token::decode($token);
            $id = $user->id;

            $name = $this->request->getVar('name');
            $data = $this->request->getVar('data');

            $fileTypes = ['image/jpeg', 'image/png', 'image/gif'];

            if (isset($name, $data, $id)) {
                list($type, $file) = explode(';', $data);
                list(, $file) = explode(',', $file);
                $file = base64_decode($file);
                file_put_contents($name, $file);
                $file_url = realpath($name);

                $uploader = new Uploader();
                $result = $uploader->uploadPath($file_url);

                $userModel->update($id, ['avatar' => $result['secure_url']]);
                $user = $userModel->find($id);
                Session::data('user', $user);
                return json_encode([
                    'result' => $result['secure_url']
                ]);
            }

            return json_encode([
                'result' => null
            ]);
        } catch (\Throwable $th) {
            return json_encode([
                'result' => $th->getMessage()
            ]);
        }
    }
}
