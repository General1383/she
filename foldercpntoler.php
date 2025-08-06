<?php

class FolderController
{
    private $folderModel;

    public function __construct()
    {
        $this->folderModel = new Folder();
    }
    private function getUserId(): int
    {
    
        $user = Auth::user();
        if ($user && isset($user['id'])) {
            return (int)$user['id'];
        }
        Response::error('Unauthorized: User ID not found in session.', 401);
    }
    public function create(array $data)
    {
        $userId = $this->getUserId(); 
        $fields = [
            'name' => ['value' => $data['name'] ?? null, 'rules' => ['required', 'min:1', 'max:255']]
        ];

        Validator::validate($fields); 

        $sanitizedData = Validator::getSanitizedData();

        $this->folderModel->name = $sanitizedData['name'];
        $this->folderModel->user_id = $userId;

        try {
            $folderId = $this->folderModel->create(); 
            if ($folderId) {
             
                $newFolder = $this->folderModel->findByIdAndUser($folderId, $userId);
                
                Response::success([
                        'id' => $newFolder['id'],
                        'name' => $newFolder['name'],
                        'user_id' => $newFolder['user_id'] 
                    ]
               , 201);
            } else {
                Response::error('Failed to create folder. Database issue or duplicate name.', 500);
            }
        } catch (Exception $e) {
            error_log("FolderController create error: " . $e->getMessage());
            Response::error('An unexpected error occurred during folder creation.', 500);
        }
    }

    public function getAll()
    {
        $userId = $this->getUserId();
        try {
            $folders = $this->folderModel->findByUserId($userId);
            Response::success(['folders' => $folders], 200);
        } catch (Exception $e) {
            error_log("FolderController getAll error: " . $e->getMessage());
            Response::error('An unexpected error occurred while fetching folders.', 500);
        }
    }

    public function getOne(int $folderId)
    {
        $userId = $this->getUserId();
        try {
            $folder = $this->folderModel->findByIdAndUser($folderId, $userId);

            if ($folder) {
                Response::success(['folder' => $folder], 200); // Return single 'folder' object
            } else {
                Response::error('Folder not found or you do not have access.', 404);
            }
        } catch (Exception $e) {
            error_log("FolderController getOne error: " . $e->getMessage());
            Response::error('An unexpected error occurred while fetching the folder.', 500);
        }
    }

    public function update(int $folderId, array $data)
    {
        $userId = $this->getUserId();


        $fields = [
            'name' => ['value' => $data['name'] ?? null, 'rules' => ['required', 'min:1', 'max:255']]
           
        ];

        Validator::validate($fields); 
        $sanitizedData = Validator::getSanitizedData();

        if (!$this->folderModel->exists($folderId, $userId)) {
            Response::error('Folder not found or you do not have access.', 404);
        }

        try {
            if ($this->folderModel->update($folderId, $userId, ['name' => $sanitizedData['name']])) {
                Response::success(['message' => 'Folder updated successfully.'], 200);
            } else {
                Response::error('Failed to update folder.', 500);
            }
        } catch (Exception $e) {
            error_log("FolderController update error: " . $e->getMessage());
            Response::error('An unexpected error occurred during folder update.', 500);
        }
    }
}
    