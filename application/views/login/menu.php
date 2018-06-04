<?php ?>
<header class="header_main">
    <div class="header">
        <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <a class="navbar-brand" href="">
                    <img src="<?php echo $this->db->get_where("settings", array("type" => "school_logo"))->row()->description ?>";
                         alt="img" height="45px" width="45px" class="img-circle img-responsive pull-left"/></a>
                <span class="school_title">
                    <?php echo $this->db->get_where("settings", array("type" => "school_name"))->row()->description; ?>
                </span>

            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">


                <!--
                    <ul class="nav navbar-nav ">
                        <li><span class="shcool_adress">Flot No. 123/566, NGO,s Colony, Vanastali Puram, Hyderabad.</span></li>
                    </ul>
                -->
                <ul class="nav navbar-nav navbar-right nav_mob">


                    <li class="dropdown user user-menu ">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <?php
                            if (!empty($this->session->user["photo"])) {
                                echo '<img src="' . $this->session->user["photo"] . '" width="35" class="img-circle img-responsive pull-left" height="35" alt="Profile Pic">';
                            }
                            ?>

                            <div class="riot">
                                <div>
                                    <p class="user_name_max">
                                        <?php echo $this->session->user["full_name"]; ?>
                                    </p>
                                    <span>
                                        <i class="caret"></i>
                                    </span>
                                </div>
                            </div>
                        </a>
                        <ul class="dropdown-menu">
                            <!-- User image -->
                            <li class="user-header bg-light-blue">

                                <?php
                                if (!empty($this->session->user["photo"])) {
                                    echo '<img src="' . $this->session->user["photo"] . '"  class="img-responsive img-circle" alt="Profile Pic">';
                                }
                                ?>
                                <p class="topprofiletext"> <?php echo $this->session->user["full_name"]; ?></p>
                            </li>

                            <li class="user-midle">
                                <a href="<?php echo getBaseUrl() . "index.php/login/profile" ?>"

                                   style="text-align: left;">
                                    <i class="livicon" data-name="user" data-s="18"></i>
                                    My Profile
                                </a>
                            </li>
                            <li class="user-midle">
                                <a href="<?php echo getBaseUrl() . "index.php/login/settings" ?>";
                                   style="text-align: left;">
                                    <i class="livicon" data-name="gears" data-s="18"></i>
                                    Account Settings
                                </a>
                            </li>
                            <!-- Menu Footer-->
                            <li class="user-footer">
                                <div class="pull-right">
                                    <a href="<?php echo getBaseUrl() . "index.php/login/logout" ?>";

                                       <i class="livicon" data-name="sign-out" data-s="18"></i>
                                        Logout
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </nav>

    </div>
</header>

<br/>

<section class="main_warp">
    <div class="container">

        <?php
        $menus = array();
        $hex = $this->db->get_where("hex_rbac", array("status" => 1, "role" => $this->session->user["role"]))->result_array();
        if (!empty($hex)) {
            foreach ($hex as $hKey => $hValue) {
                $menus[] = $hValue["menu_id"];
            }
        }

        $iconColor = array("#f9c949", "#ed5360", "#9fd661", "#599aee", "#c45dff", "#59baf2", "#f93797", "#FF8800");
        $rowCount = 0;
        $kbData["status"] = 1;
        $menuData = $this->db->where_in("id", $menus)->order_by("position", "ASC")->get_where("menu", $kbData)->result_array();
        if (!empty($menuData)) {
            foreach ($menuData as $mKey => $mValue) {
                if ($rowCount == 0 || $rowCount % 4 == 0) {
                    echo '<div class="row"><div class="col-md-12 col-sm-12 col-xs-12">';
                }

                $rowCount++;
                $menuIdTemp = $rowCount % 8;
                $menuId = ($menuIdTemp < 1 ? 8 : $menuIdTemp);
                ?>
                <div class="col-md-3 col-sm-3 col-xs-12">
                    <a class="hover_box1" href="<?php echo getBaseUrl() . "index.php/menu/index/" . $mValue["id"] ?>">
                        <div id="hexagon<?php echo $menuId ?>">
                            <div class="top_strip<?php echo $menuId ?>"></div>
                            <div class="shadow_strip<?php echo $menuId ?>"></div>
                            <div class="bottom_strip<?php echo $menuId ?>">
                                <b class="box-head<?php echo $menuId; ?>"> <?php echo ($mValue["name"]) ?></b>
                                <p><?php echo trim($mValue["description"]) ?></p>
                            </div>
                            <div class="inner-poly">
                                <div id="hexagon_1">
                                    <div class="icon1">
                                        <div class="livicon liv-cs" data-color="<?php echo $iconColor[$menuId - 1] ?> " data-hc="<?php echo $iconColor[$menuId - 1] ?>" data-n="<?php echo trim($mValue["icon"]) ?>" data-size="34"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php
                if ($rowCount % 4 == 0) {
                    echo "</div></div>";
                }
            }
        }

        if (0 < $rowCount && $rowCount % 4 != 0) {
            for ($i = $rowCount % 4; $i <= 4; $i++) {
                ?>
                <div class="col-md-3 col-sm-3 col-xs-12">

                </div>
            <?php } ?>
        </div>
    </div>
<?php } ?>     
</div>
</section>

<footer class="footer_main navbar-fixed-bottom">
    <div class="footer">
        <p class="text-center">All rights reserved @ 2018 by <a href="http://killobyte.in" target="_blank">Killobyte It Solutions</a></p>
    </div>
</footer>

