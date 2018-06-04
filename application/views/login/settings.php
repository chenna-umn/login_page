<?php ?>
<!-- Main content -->
<section class="content-header">
    <!--section starts-->
    <h1>Your Account Settings</h1>
    <ol class="breadcrumb">
        <li>
            <a href="<?php echo getBaseUrl() . "index.php/login/settings" ?>"

               <i class="livicon" data-name="gears" data-size="18" data-loop="true"></i>
                Account Settings
            </a>
        </li>
    </ol>
</section>
<div id='kbLoadError' class='text-center col-sm-12' style="margin-bottom: 1px;color: #EF6F6C;min-height: 20px;"></div>
<div id='kbLoadSuccess' class='text-center col-sm-12' style="margin-bottom: 1px;color: #00bc8c;min-height: 20px;"></div>
<div class="tab-pane fade active in">
    <div class="row">
        <div class="col-md-12 pd-top">
            <form action="#" method="post" id="kb_change_password_form" class="form-horizontal">
                <input type="hidden" name="kb_user_id" id="kb_user_id" value="<?php echo $this->session->user["id"]; ?>" />
                <div class="form-body">
                    <div class="form-group">
                        <label for="inputpassword" class="col-md-3 control-label">
                            Password
                            <span class='require'>*</span>
                        </label>
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="livicon" data-name="key" data-size="16" data-loop="true" data-c="#000" data-hc="#000"></i>
                                </span>
                                <input type="password" name="new_pass" id="new_pass" placeholder="Enter New Password" class="form-control"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <div class="col-md-offset-3 col-md-9">
                        <input type="button" id="kbUserLogin" class="btn btn-primary" value="Change Password" onClick="changePass()">
                        &nbsp;
                        <input type="reset" class="btn btn-default hidden-xs" value="Reset"></div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function changePass()
    {
        jQuery('#kbLoadSuccess').html('');
        jQuery('#kbLoadError').html('');
        var pass = $('#new_pass').val();
        var n = pass.length;
        if (n < 4)
        {
            jQuery('#kbLoadError').html('Password should have atleast 4 characters.');
        } else
        {
            jQuery('#kbUserLogin').hide();
            jQuery('#kbLoadSuccess').html('Updating...');
            $.ajax({
                url: '<?php echo getBaseUrl() . 'index.php/login/changePassword' ?>',
                type: "post",
                data: $('#kb_change_password_form').serialize(),
                success: function (response)
                {
                    jQuery('#kbLoadSuccess').html(response);
                    jQuery('#kbUserLogin').show();
                    $('#new_pass').val('');
                }
            });
        }
    }
</script>

<script src="<?php echo getBaseUrl() . "datatable/js/jquery-1.12.4.js" ?>type="text/javascript"></script>  
<script>
    $(".left-side").addClass("collapse-left");
    $(".right-side").addClass("strech");
</script>";

