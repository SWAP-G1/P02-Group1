<html>
<body>  

<form method="post" action="class_create.php">
    <center>
        <table>
            <tr>
                <td>Class Code:</td>
                <td><input type="text" name="class_code" required></td>
            </tr>
            <tr>
                <td>Course Code:</td>
                <td><input type="text" name="course_code" required></td>
            </tr>
            <tr>
                <td>Class Type:</td>
                <td>
                    <select name="class_type" required>
                        <option value="" disabled selected>Select Class Type</option>
                        <option value="Semester">Semester</option>
                        <option value="Term">Term</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: right;">
                    <input type="submit" name="submit" value="Insert Record">
                </td>
            </tr>
        </table>
    </center>
</form>

</body>
</html>
