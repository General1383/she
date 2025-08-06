<?php
class AuthController
{
    private $userModel;
    public function __construct()
    {
        $this->userModel = new User();
    }

    public function register(array $data) 
    {
   
        $fields = [
            'username' => ['value' => $data ['username'] ?? null, 'rules' => ['required', 'min:3', 'max:50']],
            'email' => ['value' => $data['email'] ?? null, 'rules' => ['required', 'email', 'unique:user,email']],
            'password' => ['value' => $data['password'] ?? null, 'rules' => ['required', 'min:6', 'passwordStrength']]
        ];

        Validator::validate($fields);

        $sanitizedData = Validator::getSanitizedData();

   
        $this->userModel->username = $sanitizedData['username'];
        $this->userModel->email = $sanitizedData['email'];
     
        $this->userModel->password = password_hash($sanitizedData['password'], PASSWORD_BCRYPT); 

        try {
            $userId = $this->userModel->create();
            if ($userId) {
                Response::success(['message' => 'Registration successful. Please log in.'], 201); // 201 Created
            } else {
                Response::error('Failed to register user. Possible duplicate email or database issue.', 500);
            }
        } catch (Exception $e) {
            error_log("AuthController register error: " . $e->getMessage());
            Response::error('An unexpected server error occurred during registration.', 500);
        }
    }

    public function login(array $data) 
    {
        $fields = [
            'email' => ['value' => $data['email'] ?? null, 'rules' => ['required', 'email']],
            'password' => ['value' => $data['password'] ?? null, 'rules' => ['required']]
        ];

        Validator::validate($fields);

        $sanitizedData = Validator::getSanitizedData();
        $email = $sanitizedData['email'];
        $password = $sanitizedData['password'];

        try {

            if (Auth::attempt($email, $password)) {
                $user = Auth::user(); 
                Response::success(['message' => 'Login successful', 'user' => $user], 200);
            } else {
                Response::error('Invalid email or password.', 401);
            }
        } catch (Exception $e) {
            error_log("AuthController login error: " . $e->getMessage());
            Response::error('An unexpected server error occurred during login.', 500);
        }
    }


}