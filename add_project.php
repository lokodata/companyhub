<?php
    session_start();

    // if session not logged in OR accountype not user go to login
    if (!isset($_SESSION["user"]) OR $_SESSION["account_type"] !== "User") {
        header("Location: login.php");
        die();
    }

    require_once "connect.php";
    require_once "errors.php";

    // Gets all output of each forms
    if (isset($_POST["submit"])) {
        $project_name = $_POST["project_name"];
        $employee_name = $_POST["employee_name"];
        $project_cost = $_POST["project_cost"];
        $project_start = $_POST["project_start"];
        $project_deadline = $_POST["project_deadline"];
        $project_description = $_POST["project_description"];

        $datetime1 = new DateTime($project_start);
        $datetime2 = new DateTime($project_deadline);

        // Calculate project duration
        $interval_duration = $datetime1->diff($datetime2);
        $years_duration = $interval_duration->y;
        $months_duration = $interval_duration->m;
        $days_duration = $interval_duration->d;

        $project_duration = $years_duration . " Years, " . $months_duration . " Months, and " . $days_duration . " Days";

        // Calculate remaining time
        $now = new DateTime();
        if ($datetime2 < $now) {
            $interval_remaining = new DateInterval('P0D');
        } else {
            $interval_remaining = $now->diff($datetime2);
        }

        $years_remaining = $interval_remaining->y;
        $months_remaining = $interval_remaining->m;
        $days_remaining = $interval_remaining->d;

        $project_timeremaining = $years_remaining . " Years, " . $months_remaining . " Months, and " . $days_remaining . " Days";

        // Check if a form is empty 
        if (empty($project_name) OR empty($employee_name) OR empty($project_cost) 
            OR empty($project_start) OR empty($project_deadline) OR empty($project_description)){
                push_error("All fields are required.");
        } else {
            // will not work if employee_name is empty
            // Split the name into first name and last name
            $name_parts = explode(' ', $employee_name);
            $first_name = isset($name_parts[0]) ? $name_parts[0] : '';
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';


            // Query the employeetbl table to check if the name exists
            $query = "SELECT * FROM employeetbl WHERE firstname = '$first_name' AND lastname = '$last_name'";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) > 0) {
                // Employee name exists in the database
                $row = mysqli_fetch_assoc($result);
                $employee_id = $row['employee_id'];
                $department = $row['department'];
                // Do something with the employee ID
            } else {
                // Employee name doesn't exist in the database
                push_error("The employee is not in the database.");
            }
        }

        // Check if project start is further than deadline
        if (strtotime($project_start) > strtotime($project_deadline)){
            push_error("Invalid Project Start or Deadline.");
        }

        // Check if there is any errors, if there is then echo the error
        if (empty($errors)){
            $query = "INSERT INTO projectstbl (project_name, employee_name, employee_id, department, project_cost, project_start, project_deadline, project_description, project_duration, project_timeremaining)
             VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_stmt_init($conn);
            $preparestmt = mysqli_stmt_prepare($stmt, $query);


            if ($preparestmt) {
                mysqli_stmt_bind_param($stmt,"ssssssssss", $project_name, $employee_name, $employee_id, $department, $project_cost, $project_start, $project_deadline, $project_description, 
                $project_duration, $project_timeremaining);
                mysqli_stmt_execute($stmt);

                header("Location: employee.php");

            } else {
                die("Something went wrong.");
            }
        }
    }            
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link rel="stylesheet" href="./style/add_project.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-dark w-100">
        <div class="container-fluid">
            <span class="navbar-brand" href="#">
            <img src="img/logo1.png" alt="Logo" width="30" height="30">
            Logo Name
            </span>

            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a href="employee.php" class="nav-link">Employee</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid container">
        <div class="header">
            <h3>Add Project</h3>
        </div>

        <form action="add_project.php" method="post" class="row form">
            <div class="col-md-12">
                <label class="form-label" for="project_name">PROJECT NAME</label>  
                <input type="text" class="form-control form-control-md" name="project_name" placeholder="Project Name">
            </div>
            <div class="col-md-12">
                <label class="form-label" for="employee_name">EMPLOYEE NAME</label>  
                <input type="text" class="form-control form-control-md" name="employee_name" placeholder="Employee Name">
            </div>
            <div class="col-md-12">
                <label class="form-label" for="project_cost">PROJECT COST</label>  
                <input type="number" class="form-control form-control-md" name="project_cost" placeholder="Project Cost">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="project_start">PROJECT START</label>  
                <input type="date" class="form-control form-control-md" name="project_start">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="project_deadline">PROJECT DEADLINE</label>  
                <input type="date" class="form-control form-control-md" name="project_deadline">
            </div>
            <div class="col-md-12">
                <label class="form-label" for="project_description">PROJECT DESCRIPTION</label>  
                <textarea class="form-control form-control-md" name="project_description" placeholder="Project Description"></textarea>
            </div>
            <div class="col button">
                <input type="submit" class="btn btn-dark btn-lg" name="submit" value="Add Project">
            </div>
        </form>

        <div class="errors">
            <?php 
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        echo "<div class='error'> <b>" . $error . "</b> <br> </div>";    
                    }
                    
                    // Reset errors after displaying them
                    reset_errors();
                }
            ?>
        </div>
    </div>
    
</body>
</html>