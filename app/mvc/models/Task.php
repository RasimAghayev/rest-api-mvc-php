<?php

class Task
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }
    // Add Post
    public function addTask($data)
    {
        // Prepare Query
        $this->db->query('INSERT INTO tasks (UserID,CompanyName,WorkName,SegmentName,WorkCount,TaskStartTime,TaskEndTime,FailureTask,Note) 
VALUES (:user_id,:CompanyName,:WorkName,:SegmentName,:WorkCount,:TaskStartTime,:TaskEndTime,:FailureTask,:Note);');

        // Bind Values
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':CompanyName', $data['CompanyName']);
        $this->db->bind(':WorkName', $data['WorkName']);
        $this->db->bind(':SegmentName', $data['SegmentName']);
        $this->db->bind(':WorkCount', $data['WorkCount']);
        $this->db->bind(':TaskStartTime', $data['TaskStartTime']);
        $this->db->bind(':TaskEndTime', $data['TaskEndTime']);
        $this->db->bind(':FailureTask', $data['FailureTask']);
        $this->db->bind(':Note', $data['Note']);
        //Execute
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    // Update Post
    public function updateTask($data)
    {
        // Prepare Query
        $this->db->query('UPDATE posts SET title = :title, body = :body WHERE id = :id');

        // Bind Values
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':body', $data['body']);

        //Execute
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    // Delete Post
    public function deleteTask($id)
    {
        // Prepare Query
        $this->db->query('DELETE FROM tasks WHERE id = :id');

        // Bind Values
        $this->db->bind(':id', $id);

        //Execute
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    // Get All Posts
    public function getTasks()
    {
        $this->db->query("SELECT *, 
                        tasks.id as TaskId, 
                        users.id as userId
                        FROM tasks 
                        INNER JOIN users 
                        ON tasks.UserID = users.id
                        ORDER BY tasks.created_at DESC;");

        $results = $this->db->resultset();

        return $results;
    }

    // Get Post By ID
    public function getTaskById($id)
    {
        $this->db->query("SELECT * FROM posts WHERE id = :id");

        $this->db->bind(':id', $id);

        $row = $this->db->single();

        return $row;
    }

}