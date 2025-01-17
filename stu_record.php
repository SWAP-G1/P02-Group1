<html>

<head>
    <style>
        #title {
            text-align: center;
        }

        table {
            margin: auto;
            border: 1px solid black;
            border-collapse: collapse;
            width: 80%;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h2 id="title">Combined User and Student Table</h2>
    <?php
    $con = mysqli_connect("localhost", "admin", "admin", "xyz polytechnic"); // Connect to database
    if (!$con) {
        die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
    }

    // Join `user` and `student` tables
    $stmt = $con->prepare("
        SELECT 
            user.identification_code, 
            user.email, 
            user.phone_number, 
            user.full_name, 
            student.class_code, 
            student.diploma_code 
        FROM user
        LEFT JOIN student ON user.identification_code = student.identification_code
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<table>';
    echo '<tr><th>Identification Code</th><th>Full Name</th><th>Email</th><th>Phone Number</th><th>Class Code</th><th>Diploma Code</th></tr>';

    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . $row['identification_code'] . '</td>';
        echo '<td>' . $row['full_name'] . '</td>';
        echo '<td>' . $row['email'] . '</td>';
        echo '<td>' . $row['phone_number'] . '</td>';
        echo '<td>' . (!empty($row['class_code']) ? $row['class_code'] : 'N/A') . '</td>';
        echo '<td>' . (!empty($row['diploma_code']) ? $row['diploma_code'] : 'N/A') . '</td>';
        echo '</tr>';
    }

    echo '</table>';
    $stmt->close();

    $con->close();
    ?>
</body>
</html>
