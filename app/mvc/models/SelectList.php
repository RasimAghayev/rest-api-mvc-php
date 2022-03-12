<?php

class SelectList
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }
    // Get Post By ID
    public function getSelectById($id)
    {
        $this->db->query("SELECT*FROM selectlist WHERE ListDescID= :id");
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        return $row;
    }

    // Add Post
    public function addPost($data)
    {
        // Prepare Query
        $this->db->query('INSERT INTO posts (title, user_id, body) 
      VALUES (:title, :user_id, :body)');

        // Bind Values
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':body', $data['body']);

        //Execute
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    // Update Post
    public function updatePost($data)
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
    public function deletePost($id)
    {
        // Prepare Query
        $this->db->query('DELETE FROM posts WHERE id = :id');

        // Bind Values
        $this->db->bind(':id', $id);

        //Execute
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }
}