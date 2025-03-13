<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Focus - Bootstrap Admin Dashboard </title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="./images/favicon.png">
    <link href="./css/style.css" rel="stylesheet">

</head>

<body>

    <!--*******************
        Preloader start
    ********************-->
    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>
    <!--*******************
        Preloader end
    ********************-->

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">


        <!--**********************************
            Nav header start
        ***********************************-->
        <div class="nav-header">
            <a href="index.php" class="brand-logo">
                <img class="logo-abbr" src="./images/logo.png" alt="">
                <img class="logo-compact" src="./images/logo-text.png" alt="">
                <img class="brand-title" src="./images/logo-text.png" alt="">
            </a>

            <div class="nav-control">
                <div class="hamburger">
                    <span class="line"></span><span class="line"></span><span class="line"></span>
                </div>
            </div>
        </div>
        <!--**********************************
            Nav header end
        ***********************************-->

        <!--**********************************
            Header start
        ***********************************-->
        <div class="header">
            <div class="header-content">
                <nav class="navbar navbar-expand">
                    <div class="collapse navbar-collapse justify-content-between">
                        <div class="header-left">
                            <div class="search_bar dropdown">
                                <span class="search_icon p-3 c-pointer" data-toggle="dropdown">
                                    <i class="mdi mdi-magnify"></i>
                                </span>
                                <div class="dropdown-menu p-0 m-0">
                                    <form>
                                        <input class="form-control" type="search" placeholder="Search" aria-label="Search">
                                    </form>
                                </div>
                            </div>
                        </div>

                        <ul class="navbar-nav header-right">
                            <li class="nav-item dropdown notification_dropdown">
                                <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                                    <i class="mdi mdi-bell"></i>
                                    <div class="pulse-css"></div>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <ul class="list-unstyled">
                                        <li class="media dropdown-item">
                                            <span class="success"><i class="ti-user"></i></span>
                                            <div class="media-body">
                                                <a href="#">
                                                    <p><strong>Martin</strong> has added a <strong>customer</strong> Successfully
                                                    </p>
                                                </a>
                                            </div>
                                            <span class="notify-time">3:20 am</span>
                                        </li>
                                        <li class="media dropdown-item">
                                            <span class="primary"><i class="ti-shopping-cart"></i></span>
                                            <div class="media-body">
                                                <a href="#">
                                                    <p><strong>Jennifer</strong> purchased Light Dashboard 2.0.</p>
                                                </a>
                                            </div>
                                            <span class="notify-time">3:20 am</span>
                                        </li>
                                        <li class="media dropdown-item">
                                            <span class="danger"><i class="ti-bookmark"></i></span>
                                            <div class="media-body">
                                                <a href="#">
                                                    <p><strong>Robin</strong> marked a <strong>ticket</strong> as unsolved.
                                                    </p>
                                                </a>
                                            </div>
                                            <span class="notify-time">3:20 am</span>
                                        </li>
                                        <li class="media dropdown-item">
                                            <span class="primary"><i class="ti-heart"></i></span>
                                            <div class="media-body">
                                                <a href="#">
                                                    <p><strong>David</strong> purchased Light Dashboard 1.0.</p>
                                                </a>
                                            </div>
                                            <span class="notify-time">3:20 am</span>
                                        </li>
                                        <li class="media dropdown-item">
                                            <span class="success"><i class="ti-image"></i></span>
                                            <div class="media-body">
                                                <a href="#">
                                                    <p><strong> James.</strong> has added a<strong>customer</strong> Successfully
                                                    </p>
                                                </a>
                                            </div>
                                            <span class="notify-time">3:20 am</span>
                                        </li>
                                    </ul>
                                    <a class="all-notification" href="#">See all notifications <i
                                            class="ti-arrow-right"></i></a>
                                </div>
                            </li>
                            <li class="nav-item dropdown header-profile">
                                <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                                    <i class="mdi mdi-account"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="./app-profile.php" class="dropdown-item">
                                        <i class="icon-user"></i>
                                        <span class="ml-2">Profile </span>
                                    </a>
                                    <a href="./email-inbox.php" class="dropdown-item">
                                        <i class="icon-envelope-open"></i>
                                        <span class="ml-2">Inbox </span>
                                    </a>
                                    <a href="./page-login.php" class="dropdown-item">
                                        <i class="icon-key"></i>
                                        <span class="ml-2">Logout </span>
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>
        <!--**********************************
            Header end ti-comment-alt
        ***********************************-->

        <!--**********************************
            Sidebar start
        ***********************************-->
        <div class="quixnav">
            <div class="quixnav-scroll">
                <ul class="metismenu" id="menu">
                    <li class="nav-label first">Main Menu</li>
                    <!-- <li><a href="index.php"><i class="icon icon-single-04"></i><span class="nav-text">Dashboard</span></a>
                    </li> -->
                    <li><a class="has-arrow" href="javascript:void()" aria-expanded="false"><i
                                class="icon icon-single-04"></i><span class="nav-text">Dashboard</span></a>
                        <ul aria-expanded="false">
                            <li><a href="./index.php">Dashboard 1</a></li>
                            <li><a href="./index2.php">Dashboard 2</a></li>

                        </ul>
                    </li>
                    <li class="nav-label">Layout</li>
                    <li><a class="has-arrow" href="javascript:void()" aria-expanded="false"><i
                                class="icon icon-layers-3"></i><span class="nav-text">Option</span></a>
                        <ul aria-expanded="false">
                            <li><a href="./layout-blank.php">Blank</a></li>
                            <li><a href="./layout-dark.php">Dark</a></li>
                            <li><a href="./layout-light.php">Light</a></li>
                            <li><a href="./layout-full-nav.php">Full Nav</a></li>
                            <li><a href="./layout-compact-nav.php">Compact Nav</a></li>
                            <li><a href="./layout-mini-nav.php">Mini Nav</a></li>
                            <li><a href="./layout-fixed-header.php">Fixed Header</a></li>
                            <li><a href="./layout-fixed-nav.php">Fixed Sidebar</a></li>
                            <li><a href="./layout-rtl.php">RTL</a></li>
                        </ul>
                    </li>
                    <li class="nav-label">Apps</li>
                    <li><a class="has-arrow" href="javascript:void()" aria-expanded="false"><i
                                class="icon icon-app-store"></i><span class="nav-text">Apps</span></a>
                        <ul aria-expanded="false">
                            <li><a href="./app-profile.php">Profile</a></li>
                            <li><a class="has-arrow" href="javascript:void()" aria-expanded="false">Email</a>
                                <ul aria-expanded="false">
                                    <li><a href="./email-compose.php">Compose</a></li>
                                    <li><a href="./email-inbox.php">Inbox</a></li>
                                    <li><a href="./email-read.php">Read</a></li>
                                </ul>
                            </li>
                            <li><a href="./app-calender.php">Calendar</a></li>
                        </ul>
                    </li>
                    <li><a class="has-arrow" href="javascript:void()" aria-expanded="false"><i
                                class="icon icon-chart-bar-33"></i><span class="nav-text">Charts</span></a>
                        <ul aria-expanded="false">
                            <li><a href="./chart-flot.php">Flot</a></li>
                            <li><a href="./chart-morris.php">Morris</a></li>
                            <li><a href="./chart-chartjs.php">Chartjs</a></li>
                            <li><a href="./chart-chartist.php">Chartist</a></li>
                            <li><a href="./chart-sparkline.php">Sparkline</a></li>
                            <li><a href="./chart-peity.php">Peity</a></li>
                        </ul>
                    </li>
                    <li class="nav-label">Components</li>
                    <li><a class="has-arrow" href="javascript:void()" aria-expanded="false"><i
                                class="icon icon-world-2"></i><span class="nav-text">Bootstrap</span></a>
                        <ul aria-expanded="false">
                            <li><a href="./ui-accordion.php">Accordion</a></li>
                            <li><a href="./ui-alert.php">Alert</a></li>
                            <li><a href="./ui-badge.php">Badge</a></li>
                            <li><a href="./ui-button.php">Button</a></li>
                            <li><a href="./ui-modal.php">Modal</a></li>
                            <li><a href="./ui-button-group.php">Button Group</a></li>
                            <li><a href="./ui-list-group.php">List Group</a></li>
                            <li><a href="./ui-media-object.php">Media Object</a></li>
                            <li><a href="./ui-card.php">Cards</a></li>
                            <li><a href="./ui-carousel.php">Carousel</a></li>
                            <li><a href="./ui-dropdown.php">Dropdown</a></li>
                            <li><a href="./ui-popover.php">Popover</a></li>
                            <li><a href="./ui-progressbar.php">Progressbar</a></li>
                            <li><a href="./ui-tab.php">Tab</a></li>
                            <li><a href="./ui-typography.php">Typography</a></li>
                            <li><a href="./ui-pagination.php">Pagination</a></li>
                            <li><a href="./ui-grid.php">Grid</a></li>

                        </ul>
                    </li>

                    <li><a class="has-arrow" href="javascript:void()" aria-expanded="false"><i
                                class="icon icon-plug"></i><span class="nav-text">Plugins</span></a>
                        <ul aria-expanded="false">
                            <li><a href="./uc-select2.php">Select 2</a></li>
                            <li><a href="./uc-nestable.php">Nestedable</a></li>
                            <li><a href="./uc-noui-slider.php">Noui Slider</a></li>
                            <li><a href="./uc-sweetalert.php">Sweet Alert</a></li>
                            <li><a href="./uc-toastr.php">Toastr</a></li>
                            <li><a href="./map-jqvmap.php">Jqv Map</a></li>
                        </ul>
                    </li>
                    <li><a href="widget-basic.php" aria-expanded="false"><i class="icon icon-globe-2"></i><span
                                class="nav-text">Widget</span></a></li>
                    <li class="nav-label">Forms</li>
                    <li><a class="has-arrow" href="javascript:void()" aria-expanded="false"><i
                                class="icon icon-form"></i><span class="nav-text">Forms</span></a>
                        <ul aria-expanded="false">
                            <li><a href="./form-element.php">Form Elements</a></li>
                            <li><a href="./form-wizard.php">Wizard</a></li>
                            <li><a href="./form-editor-summernote.php">Summernote</a></li>
                            <li><a href="form-pickers.php">Pickers</a></li>
                            <li><a href="form-validation-jquery.php">Jquery Validate</a></li>
                        </ul>
                    </li>
                    <li class="nav-label">Table</li>
                    <li><a class="has-arrow" href="javascript:void()" aria-expanded="false"><i
                                class="icon icon-layout-25"></i><span class="nav-text">Table</span></a>
                        <ul aria-expanded="false">
                            <li><a href="table-bootstrap-basic.php">Bootstrap</a></li>
                            <li><a href="table-datatable-basic.php">Datatable</a></li>
                        </ul>
                    </li>

                    <li class="nav-label">Extra</li>
                    <li><a class="has-arrow" href="javascript:void()" aria-expanded="false"><i
                                class="icon icon-single-copy-06"></i><span class="nav-text">Pages</span></a>
                        <ul aria-expanded="false">
                            <li><a href="./page-register.php">Register</a></li>
                            <li><a href="./page-login.php">Login</a></li>
                            <li><a class="has-arrow" href="javascript:void()" aria-expanded="false">Error</a>
                                <ul aria-expanded="false">
                                    <li><a href="./page-error-400.php">Error 400</a></li>
                                    <li><a href="./page-error-403.php">Error 403</a></li>
                                    <li><a href="./page-error-404.php">Error 404</a></li>
                                    <li><a href="./page-error-500.php">Error 500</a></li>
                                    <li><a href="./page-error-503.php">Error 503</a></li>
                                </ul>
                            </li>
                            <li><a href="./page-lock-screen.php">Lock Screen</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <!--**********************************
            Sidebar end
        ***********************************-->

        <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body badge-demo">
            <div class="container-fluid">
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4>Hi, welcome back!</h4>
                            <span class="ml-1">Badge</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0)">Bootstrap</a></li>
                            <li class="breadcrumb-item active"><a href="javascript:void(0)">Badge</a></li>
                        </ol>
                    </div>
                </div>
                <!-- row -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-block">
                                <h4 class="card-title">Badges </h4>
                                <p class="mb-0 subtitle">Default Bootstrap Badges</p>
                            </div>
                            <div class="card-body">
                                <div class="bootstrap-badge">
                                    <span class="badge badge-primary">Primary</span>
                                    <span class="badge badge-secondary">Secondary</span>
                                    <span class="badge badge-success">Success</span>
                                    <span class="badge badge-danger">Danger</span>
                                    <span class="badge badge-warning">Warning</span>
                                    <span class="badge badge-info">Info</span>
                                    <span class="badge badge-light">Light</span>
                                    <span class="badge badge-dark">Dark</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-block">
                                <h4 class="card-title">Pill Badge </h4>
                                <p class="mb-0 subtitle">add <code>.badge-pill</code> to change the style</p>
                            </div>
                            <div class="card-body">
                                <div class="bootstrap-badge">
                                    <span class="badge badge-pill badge-primary">Pill badge</span>
                                    <span class="badge badge-pill badge-secondary">Pill badge</span>
                                    <span class="badge badge-pill badge-success">Pill badge</span>
                                    <span class="badge badge-pill badge-danger">Pill badge</span>
                                    <span class="badge badge-pill badge-warning">Pill badge</span>
                                    <span class="badge badge-pill badge-info">Pill badge</span>
                                    <span class="badge badge-pill badge-light">Pill badge</span>
                                    <span class="badge badge-pill badge-dark">Pill badge</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-block">
                                <h4 class="card-title">Link Badge </h4>
                                <p class="mb-0 subtitle">Link badge add in anchor tag</p>
                            </div>
                            <div class="card-body">
                                <div class="bootstrap-badge">
                                    <a href="javascript:void()" class="badge badge-primary">Links</a>
                                    <a href="javascript:void()" class="badge badge-secondary">Links</a>
                                    <a href="javascript:void()" class="badge badge-success">Links</a>
                                    <a href="javascript:void()" class="badge badge-danger">Links</a>
                                    <a href="javascript:void()" class="badge badge-warning">Links</a>
                                    <a href="javascript:void()" class="badge badge-info">Links</a>
                                    <a href="javascript:void()" class="badge badge-light">Links</a>
                                    <a href="javascript:void()" class="badge badge-dark">Links</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-block">
                                <h4 class="card-title">Rounded Badge </h4>
                                <p class="mb-0 subtitle">add <code>.badge-rounded</code> to change the style</p>
                            </div>
                            <div class="card-body">
                                <div class="bootstrap-badge">
                                    <a href="javascript:void()" class="badge badge-rounded badge-primary">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-secondary">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-success">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-danger">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-warning">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-info">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-light">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-dark">Rounded</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-block">
                                <h4 class="card-title">Rounded Outline Badge </h4>
                                <p class="mb-0 subtitle">add <code>.badge-rounded</code> to change the style</p>
                            </div>
                            <div class="card-body">
                                <div class="bootstrap-badge">
                                    <a href="javascript:void()" class="badge badge-rounded badge-outline-primary">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-outline-secondary">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-outline-success">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-outline-danger">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-outline-warning">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-outline-info">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-outline-light">Rounded</a>
                                    <a href="javascript:void()" class="badge badge-rounded badge-outline-dark">Rounded</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-block">
                                <h4 class="card-title">Outline Circle Badge </h4>
                                <p class="mb-0 subtitle">add <code>.badge-circle</code> to change the style</p>
                            </div>
                            <div class="card-body">
                                <div class="bootstrap-badge">
                                    <a href="javascript:void()" class="badge badge-circle badge-outline-primary">1</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-outline-secondary">2</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-outline-success">3</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-outline-danger">4</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-outline-warning">5</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-outline-info">6</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-outline-light">7</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-outline-dark">8</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-block">
                                <h4 class="card-title">Circle Badge </h4>
                                <p class="mb-0 subtitle">add <code>.badge-circle</code> to change the style</p>
                            </div>
                            <div class="card-body">
                                <div class="bootstrap-badge">
                                    <a href="javascript:void()" class="badge badge-circle badge-primary">1</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-secondary">2</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-success">3</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-danger">4</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-warning">5</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-info">6</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-light">7</a>
                                    <a href="javascript:void()" class="badge badge-circle badge-dark">8</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-block">
                                <h4 class="card-title">Outline Badge </h4>
                                <p class="mb-0 subtitle">Default bootstrap outline baadge</p>
                            </div>
                            <div class="card-body">
                                <div class="bootstrap-badge">
                                    <a href="javascript:void()" class="badge badge-outline-primary">1</a>
                                    <a href="javascript:void()" class="badge badge-outline-secondary">2</a>
                                    <a href="javascript:void()" class="badge badge-outline-success">3</a>
                                    <a href="javascript:void()" class="badge badge-outline-danger">4</a>
                                    <a href="javascript:void()" class="badge badge-outline-warning">5</a>
                                    <a href="javascript:void()" class="badge badge-outline-info">6</a>
                                    <a href="javascript:void()" class="badge badge-outline-light">7</a>
                                    <a href="javascript:void()" class="badge badge-outline-dark">8</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-block">
                                <h4 class="card-title">Number Badge </h4>
                                <p class="mb-0 subtitle">Default bootstrap outline baadge</p>
                            </div>
                            <div class="card-body">
                                <div class="bootstrap-badge">
                                    <a href="javascript:void()" class="badge badge-primary">1</a>
                                    <a href="javascript:void()" class="badge badge-secondary">2</a>
                                    <a href="javascript:void()" class="badge badge-success">3</a>
                                    <a href="javascript:void()" class="badge badge-danger">4</a>
                                    <a href="javascript:void()" class="badge badge-warning">5</a>
                                    <a href="javascript:void()" class="badge badge-info">6</a>
                                    <a href="javascript:void()" class="badge badge-light">7</a>
                                    <a href="javascript:void()" class="badge badge-dark">8</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-block">
                                <h4 class="card-title">Badge Sizes </h4>
                                <p class="mb-0 subtitle">add <code>.badge-xs .badge-sm .badge-md .badge-lg
                                        .badge-xl</code> to change the style</p>
                            </div>
                            <div class="card-body">
                                <div class="bootstrap-badge">
                                    <a href="javascript:void()" class="badge badge-xs badge-primary">xs</a>
                                    <a href="javascript:void()" class="badge badge-sm badge-secondary">sm</a>
                                    <a href="javascript:void()" class="badge badge-md badge-success">md</a>
                                    <a href="javascript:void()" class="badge badge-lg badge-danger">lg</a>
                                    <a href="javascript:void()" class="badge badge-xl badge-warning">xl</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--**********************************
            Content body end
        ***********************************-->


        <!--**********************************
            Footer start
        ***********************************-->
        <div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <a href="#" target="_blank">Quixkit</a> 2019</p>
            </div>
        </div>
        <!--**********************************
            Footer end
        ***********************************-->

        <!--**********************************
           Support ticket button start
        ***********************************-->

        <!--**********************************
           Support ticket button end
        ***********************************-->


    </div>
    <!--**********************************
        Main wrapper end
    ***********************************-->

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="./vendor/global/global.min.js"></script>
    <script src="./js/quixnav-init.js"></script>
    <script src="./js/custom.min.js"></script>

</body>

</html>
