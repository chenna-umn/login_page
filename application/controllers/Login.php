<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
		
		$this->load->library('session');
        $this->load->helper("url");
        $this->_init();
    }

    private function _init()
    {
        $this->output->set_template("login");
    }

    public function index()
    {
        if( isset($this->session->user["login_id"]) && !empty($this->session->user["login_id"]) ) 
        {
            redirect(getBaseUrl() . "index.php/login/menu", "refresh");
        }

        $this->output->set_template("login");
        $this->load->view("login/index");
    }

    public function checkLogin()
    {
        if( isset($_POST["email"]) && isset($_POST["password"]) ) 
        {
            $kbData["login_id"] = $_POST["email"];
            $kbData["password"] = md5($_POST["password"]);
            $kbData["status"] = 1;
            $finalData = $this->db->get_where("staff", $kbData)->result_array();
            if( !empty($finalData[0]) ) 
            {
                $finalData[0]["full_name"] = $finalData[0]["fname"] . " " . $finalData[0]["lname"];
                $finalData[0]["is_teacher"] = 2;
                $editData = $this->db->get_where("settings", array( "type" => "teacher_designation", "status" => 1 ))->row();
                if( isset($editData->description) && $finalData[0]["desg"] == $editData->description ) 
                {
                    $finalData[0]["is_teacher"] = 1;
                }

                $this->session->set_userdata("user", $finalData[0]);
                echo "1";
                exit();
            }

            $finalData = $this->db->get_where("admin", $kbData)->result_array();
            if( !empty($finalData[0]) ) 
            {
                $finalData[0]["full_name"] = $finalData[0]["fname"] . " " . $finalData[0]["lname"];
                $this->session->set_userdata("user", $finalData[0]);
                echo "1";
                exit();
            }

            $finalData = $this->db->get_where("student", $kbData)->result_array();
            if( !empty($finalData[0]) ) 
            {
                $finalData[0]["full_name"] = $finalData[0]["fname"] . " " . $finalData[0]["lname"];
                $finalData[0]["is_teacher"] = 2;
                $acadYear = 0;
                $editData = $this->db->get_where("settings", array( "type" => "academic_year", "status" => 1 ))->row();
                if( isset($editData->description) ) 
                {
                    $acadYear = $editData->description;
                }

                if( !empty($acadYear) ) 
                {
                    $stuData = $this->db->get_where("student_enroll", array( "academic_year" => $acadYear, "student_id" => $finalData[0]["id"] ))->row();
                    if( isset($stuData->id) ) 
                    {
                        $finalData[0]["class"] = $stuData->class_id;
                        $finalData[0]["section"] = $stuData->section_id;
                    }

                }
                else
                {
                    $finalData[0]["class"] = "";
                    $finalData[0]["section"] = "";
                }

                $this->session->set_userdata("user", $finalData[0]);
                echo "1";
                exit();
            }

        }

        echo "Please Enter Valid LoginId / Password.";
        exit();
    }

    public function logout()
    {
        unset($_SESSION["user"]);
        redirect(getBaseUrl(), "refresh");
    }

    public function menu()
    {
        if( !isset($this->session->user["login_id"]) ) 
        {
            redirect(getBaseUrl(), "refresh");
        }

        $this->output->set_template("hexagon");
        $this->load->view("login/menu");
    }

    public function profile()
    {
        if( !isset($this->session->user["login_id"]) ) 
        {
            redirect(getBaseUrl(), "refresh");
        }

        $page_data = "";
        $this->output->set_template("kbu");
        if( $this->session->user["role"] <= 2 ) 
        {
            $this->load->view("login/profileAdmin", array( "page_data" => $page_data ));
        }
        else
        {
            if( $this->session->user["role"] == 6 ) 
            {
                $this->load->view("login/profileStudent", array( "page_data" => $page_data ));
            }
            else
            {
                $this->load->view("login/profile", array( "page_data" => $page_data ));
            }

        }

    }

    public function settings()
    {
        if( !isset($this->session->user["login_id"]) ) 
        {
            redirect(getBaseUrl(), "refresh");
        }

        $page_data = "";
        $this->output->set_template("kbu");
        if( $this->session->user["role"] <= 2 ) 
        {
            $this->load->view("login/settingsAdmin", array( "page_data" => $page_data ));
        }
        else
        {
            if( $this->session->user["role"] == 6 ) 
            {
                $this->load->view("login/settingsStudent", array( "page_data" => $page_data ));
            }
            else
            {
                $this->load->view("login/settings", array( "page_data" => $page_data ));
            }

        }

    }

    public function changePassword()
    {
        if( !isset($this->session->user["login_id"]) ) 
        {
            redirect(getBaseUrl(), "refresh");
        }

        if( $_POST ) 
        {
            $this->db->set("password", md5($_POST["new_pass"]));
            $this->db->where("id", $_POST["kb_user_id"]);
            if( $this->db->update("user") ) 
            {
                echo "Password Sucessfully Updated.";
                exit();
            }

        }

        echo "<span style='color:#EF6F6C;'>Password Updation Failed.</span>";
        exit();
    }

    public function changePasswordStudent()
    {
        if( !isset($this->session->user["login_id"]) ) 
        {
            redirect(getBaseUrl(), "refresh");
        }

        if( $_POST ) 
        {
            $this->db->set("password", md5($_POST["new_pass"]));
            $this->db->where("id", $_POST["kb_user_id"]);
            if( $this->db->update("student") ) 
            {
                echo "Password Sucessfully Updated.";
                exit();
            }

        }

        echo "<span style='color:#EF6F6C;'>Password Updation Failed.</span>";
        exit();
    }

    public function changePasswordAdmin()
    {
        if( !isset($this->session->user["login_id"]) ) 
        {
            redirect(getBaseUrl(), "refresh");
        }

        if( $_POST ) 
        {
            $this->db->set("password", md5($_POST["new_pass"]));
            $this->db->where("id", $_POST["kb_user_id"]);
            if( $this->db->update("admin") ) 
            {
                echo "Password Sucessfully Updated.";
                exit();
            }

        }

        echo "<span style='color:#EF6F6C;'>Password Updation Failed.</span>";
        exit();
    }

    public function upload()
    {
        if( !isset($this->session->user["login_id"]) ) 
        {
            redirect(getBaseUrl(), "refresh");
        }

        $page_data["error"] = "PLEASE SELECT AN IMAGE";
        if( isset($_FILES["image"]["tmp_name"]) && !empty($_FILES["image"]["tmp_name"]) ) 
        {
            if( getimagesize($_FILES["image"]["tmp_name"]) == false ) 
            {
            }
            else
            {
                $page_data["error"] = "";
                $image = addslashes($_FILES["image"]["tmp_name"]);
                $name = addslashes($_FILES["image"]["name"]);
                $image = file_get_contents($image);
                $image = "data:image;base64," . base64_encode($image);
                $this->db->set("photo", $image);
                $this->db->where("id", $this->session->user["id"]);
                if( $this->db->update("user") ) 
                {
                    $kbData["id"] = $this->session->user["id"];
                    $finalData = $this->db->get_where("user", $kbData)->result_array();
                    $this->session->set_userdata("user", $finalData[0]);
                    $page_data["success"] = "Image Successfully Uploaded. To apply changes please Logout and Login.";
                }
                else
                {
                    $page_data["error"] = "Image Upload Failed";
                }

            }

        }

        $this->output->set_template("kbu");
        $this->load->view("login/profile", array( "page_data" => $page_data ));
    }

    
    public function website()
    {
        if( !isset($this->session->user["login_id"]) ) 
        {
            redirect(getBaseUrl(), "refresh");
        }

        $this->output->set_template("blank");
        $this->db->set("description", $_POST["title"]);
        $this->db->where("type", "title");
        $this->db->update("settings");
        $this->db->set("description", $_POST["school_name"]);
        $this->db->where("type", "school_name");
        $this->db->update("settings");
        if( isset($_FILES["school_logo"]["tmp_name"]) && !empty($_FILES["school_logo"]["tmp_name"]) ) 
        {
            if( getimagesize($_FILES["school_logo"]["tmp_name"]) == false ) 
            {
            }
            else
            {
                $baseImgLoc = "uploads_org/" . "school_logo.png";
                move_uploaded_file($_FILES["school_logo"]["tmp_name"], $baseImgLoc);
                $imgLoc = getBaseUrl() . $baseImgLoc;
                $_POST["school_logo"] = "data:image;base64," . base64_encode(file_get_contents($imgLoc));
                $this->db->set("description", $_POST["school_logo"]);
                $this->db->where("type", "school_logo");
                $this->db->update("settings");
            }

        }

        echo "<span style=\"color:green;\">Request Successfully Completed.</span>";
    }

}


