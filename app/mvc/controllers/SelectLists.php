<?php

class SelectLists extends Controller
{
    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            redirect('users/login');
        }
        // Load Models
        $this->dashboardModel = $this->model('SelectList');
        $this->userModel = $this->model('User');
    }

    // Load All Posts
    public function index()
    {
//        $dashboards = $this->dashboardModel->getPosts();
//
//        $data = [
//            'dashboards' => $dashboards
//        ];
$data=[];
        $this->view('dashboards/index', $data);
    }

}