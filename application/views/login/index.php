<?php ?>
<div class="container-fluid" id="schoolHead">
    <div class="row" id="subSchoolHead">
        <div class="col-sm-12">
            <center>
                <h2 id="mainSchoolName">
                    <?php echo $this->db->get_where("settings", array("type" => "school_name"))->row()->description; ?>
                </h2>
                <p class="addressSchool">

                    <?php echo $this->db->get_where("settings", array("type" => "school_addr1"))->row()->description; ?>
                </p>
                <p class="addressSchool">

                    <?php echo $this->db->get_where("settings", array("type" => "school_addr2"))->row()->description; ?>
                </p>
                <p class="addressSchool">

                    <?php echo $this->db->get_where("settings", array("type" => "school_addr3"))->row()->description; ?>
                </p>
            </center>
        </div>


    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-sm-4 col-md-4 col-lg-4"></div>
        <!--        <div class="col-sm-6 col-md-4 col-md-offset-4">-->
        <div class="col-sm-4 col-md-4 col-lg-4">    
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong> Sign in to continue</strong>
                </div>
                <div class="panel-body">
                    <form id="kb_user_form" accept-charset="UTF-8" role="form" class="form-signin">
                        <fieldset>
                            <div class="row">
                                <div class="center-block">
                                    <img class="profile-img" src="<?php echo getBaseUrl() . "uploads_org/school_logo.png" ?>" alt="" >
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-md-10  col-md-offset-1 ">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <span class="input-group-addon">
                                                <i class="glyphicon glyphicon-user"></i>
                                            </span> 
                                            <input id="email" name="email" class="form-control" placeholder="Login Id" type="text" autofocus>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <span class="input-group-addon">
                                                <i class="glyphicon glyphicon-lock"></i>
                                            </span>
                                            <input type="password" id="password" name="password" class="form-control" placeholder="Password">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <p class="login button">
                                            <input type="button" value="Login" class="btn btn-lg btn-primary btn-block" onclick="kbUserValidate();" id="kbUserLogin" />
                                        </p>

                                    </div>
                                    <div id='kbLoadError' class='text-center col-sm-12' style="margin-bottom: 1px;color: #EF6F6C;min-height: 20px;"></div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>
                <div class="panel-footer ">
                    <!--Don't have an account! <a href="#" onClick=""> Sign Up Here </a>-->
                    <a href="http://killobyte.in" target="_blank">
                        <center><small>© Killobyte It Solutions</small></center>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-sm-4 col-md-4 col-lg-4"></div>

    </div>
</div>

<a href="http://killobyte.in" target="_blank" style="position:fixed;bottom: 0px;right: 0px;margin: 2px;">
    <img src="<?php echo getBaseUrl() . "assets/prod_logo.png" ?>" alt="My School Software" style="width:75px;">
</a>
<style>
    .panel-heading {
        padding: 5px 15px;
        text-align: center;
    }

    .panel-footer {
        padding: 1px 15px;
        color: #A0A0A0;
    }

    .profile-img {
        width: 96px;
        height: 96px;
        margin: 0 auto 10px;
        display: block;
        -moz-border-radius: 50%;
        -webkit-border-radius: 50%;
        border-radius: 50%;
    }
</style>

<script>
    function kbUserValidate()
    {
        var username = $('#email').val();
        var password = $('#password').val();
        if (username === '' || password === '')
        {
            jQuery('#kbLoadError').html('Login Id/Password cannot be empty.');
        } else
        {
            jQuery('#kbUserLogin').hide();
            jQuery('#kbLoadError').html('Authenticating...');
            $.ajax({
                url: '<?php echo getBaseUrl() . 'index.php/login/checkLogin' ?>',
                type: "post",
                data: $('#kb_user_form').serialize(),
                success: function (response)
                {
                    jQuery('#kbLoadError').html('');
                    if (response == 1)
                    {
                        $(location).attr('href', '<?php echo getBaseUrl() . 'index.php/login/menu' ?>');
                        return false;
                    } else
                    {
                        jQuery('#kbLoadError').html(response);
                        jQuery('#kbUserLogin').show();
                    }
                }
            });

        }
//    alert('Please Enter Valid Username / Password.');
    }
</script>";

