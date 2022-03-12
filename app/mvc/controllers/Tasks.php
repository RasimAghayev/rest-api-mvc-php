<?php

class Tasks extends Controller
{
    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            redirect('users/login');
        }
        // Load Models
        $this->taskModel = $this->model('Task');
        $this->userModel = $this->model('User');
    }
    // Add Post
    public function add()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitize POST
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'user_id' => $_SESSION['user_id'],
                'CompanyName' => trim($_POST['CompanyName']),
                'WorkName' => trim($_POST['WorkName']),
                'SegmentName' => trim($_POST['SegmentName']),
                'WorkCount' => trim($_POST['WorkCount']),
                'TaskStartTime' => trim($_POST['TaskStartTime']),
                'TaskEndTime' => trim($_POST['TaskEndTime']),
                'TaskSpentTime' => trim($_POST['TaskSpentTime']),
                'FailureTask' => trim($_POST['FailureTask']),
                'Note' => trim($_POST['Note']),
                'CompanyName_err' => '',
                'WorkName_err' => '',
                'SegmentName_err' => '',
                'WorkCount_err' => '',
                'TaskStartTime_err' => '',
                'TaskEndTime_err' => '',
                'TaskSpentTime_err' => '',
                'FailureTask_err' => '',
                'Note_err' => '',
            ];
            // Validate email
            if (empty($data['CompanyName'])){
                $data['CompanyName_err'] = 'CompanyName';
            }
            if (empty($data['WorkName'])){
                $data['WorkName_err'] = 'WorkName';
            }
            if (empty($data['SegmentName'])){
                $data['SegmentName_err'] = 'SegmentName';
            }
            if (empty($data['WorkCount'])){
                $data['WorkCount_err'] = 'WorkCount';
            }
            if (empty($data['TaskStartTime'])){
                $data['TaskStartTime_err'] = 'TaskStartTime';
            }
            if (empty($data['TaskEndTime'])){
                $data['TaskEndTime_err'] = 'TaskEndTime';
            }
            if (empty($data['FailureTask'])){
                $data['FailureTask_err'] = 'FailureTask';
            }
            if (empty($data['Note'])){
                $data['Note_err'] = 'Note';
            }
            // Make sure there are no errors
            if (empty($data['CompanyName_err']) &&
                empty($data['WorkName_err']) &&
                empty($data['SegmentName_err']) &&
                empty($data['WorkCount_err']) &&
                empty($data['TaskStartTime_err']) &&
                empty($data['TaskEndTime_err']) &&
                empty($data['FailureTask_err']) &&
                empty($data['Note_err'])) {
                // Validation passed
                //Execute

                if ($this->taskModel->addTask($data)) {
                    // Redirect to login
                    flash('task_added', 'Task Added');
                    redirect('tasks');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('tasks/add', $data);
            }

        } else {
            $data = [
                'CompanyName' =>'',
                'WorkName' => '',
                'SegmentName' => '',
                'WorkCount' => '',
                'TaskStartTime' => '',
                'TaskEndTime' => '',
                'FailureTask' => '',
                'Note' => '',
            ];

            $this->view('tasks/add', $data);
        }
    }
    // Edit Post
    public function edit($id)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitize POST
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'id' => $id,
                'title' => trim($_POST['title']),
                'body' => trim($_POST['body']),
                'user_id' => $_SESSION['user_id'],
                'title_err' => '',
                'body_err' => ''
            ];

            // Validate email
            if (empty($data['title'])) {
                $data['title_err'] = 'Please enter name';
                // Validate name
                if (empty($data['body'])) {
                    $data['body_err'] = 'Please enter the post body';
                }
            }

            // Make sure there are no errors
            if (empty($data['title_err']) && empty($data['body_err'])) {
                // Validation passed
                //Execute
                if ($this->postModel->updatePost($data)) {
                    // Redirect to login
                    flash('post_message', 'Post Updated');
                    redirect('dashboards');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('dashboards/edit', $data);
            }

        } else {
            // Get post from model
            $post = $this->postModel->getPostById($id);

            // Check for owner
            if ($post->user_id != $_SESSION['user_id']) {
                redirect('dashboards');
            }

            $data = [
                'id' => $id,
                'title' => $post->title,
                'body' => $post->body,
            ];

            $this->view('dashboards/edit', $data);
        }
    }
    // Show Single Post
    public function show($id)
    {
        $post = $this->taskModel->getTaskById($id);
        $user = $this->userModel->getUserById($post->user_id);

        $data = [
            'task' => $post,
            'user' => $user
        ];

        $this->view('tasks/show', $data);
    }
    // Delete Post
    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            //Execute
            if ($this->postModel->deletePost($id)) {
                // Redirect to login
                flash('post_message', 'Post Removed');
                redirect('dashboards');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('dashboards');
        }
    }
    // Load All Posts
    public function index()
    {
        $tasks = $this->taskModel->getTasks();
        $data = [
            'tasks' => $tasks
        ];
//        $data=[];
        $this->view('tasks/index', $data);
    }
}