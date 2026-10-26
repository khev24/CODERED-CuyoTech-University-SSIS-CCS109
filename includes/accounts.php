<?php
require_once __DIR__ . '/auth.php';

function save_account(array $admin, ?int $id = null): string
{
    return transaction(function () use ($admin, $id): string {
        $old = $id ? one('SELECT * FROM accounts WHERE account_id=? FOR UPDATE', [$id]) : null;
        if ($id && !$old) {
            throw new DomainException('Account not found.');
        }
        $email = required('email', 254);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            field_error('email', 'Enter a valid email address.');
        }
        if (one('SELECT account_id FROM accounts WHERE email=? AND account_id<>?', [$email, $id ?? 0])) {
            field_error('email', 'This email already belongs to an account.');
        }
        $firstName = required('first_name', 200);
        $lastName = required('last_name', 200);
        $role = choice('role', ['STUDENT', 'ADMIN', 'REGISTRAR', 'CASHIER', 'DEPARTMENT']);
        $kind = $role === 'STUDENT' ? 'STUDENT' : 'STAFF';
        if ($old && $old['account_role'] !== $kind) {
            throw new DomainException('Student/staff account kind is immutable. Create a separate account.');
        }
        $scope = $role === 'STUDENT' ? null : choice('access_scope', ['UNIVERSITY', 'DEPARTMENT']);
        if ($role === 'ADMIN' && $scope !== 'UNIVERSITY') {
            throw new DomainException('Administrators require university-wide scope.');
        }
        if ($role === 'DEPARTMENT' && $scope !== 'DEPARTMENT') {
            throw new DomainException('Department personnel require department scope.');
        }
        $department = ($role === 'STUDENT' || $scope === 'DEPARTMENT') && input('department_id') !== ''
            ? positive_id('department_id') : null;
        if (($role === 'STUDENT' || $scope === 'DEPARTMENT') && !$department) {
            field_error('department_id', 'Select the assigned department.');
        }
        if ($department && !one('SELECT department_id FROM departments WHERE department_id=?', [$department])) {
            throw new DomainException('Department not found.');
        }
        if ($id === (int) $admin['account_id'] && $role !== 'ADMIN') {
            throw new DomainException('You cannot remove your own administrator role.');
        }
        $password = $_POST['password'] ?? '';
        if (!is_string($password) || ((!$id || $password !== '') && (strlen($password) < 12 || strlen($password) > 72))) {
            field_error('password', 'Use a password between 12 and 72 bytes.');
        }
        if ($id) {
            query('UPDATE accounts SET email=? WHERE account_id=?', [$email, $id]);
            if ($password !== '') {
                query('UPDATE accounts SET password_hash=? WHERE account_id=?', [password_hash($password, PASSWORD_DEFAULT), $id]);
            }
        } else {
            query('INSERT INTO accounts (email,password_hash,account_role) VALUES (?,?,?)', [$email, password_hash($password, PASSWORD_DEFAULT), $kind]);
            $id = (int) db()->lastInsertId();
        }
        if ($kind === 'STUDENT') {
            $number = required('student_number', 30);
            if (one('SELECT student_id FROM students WHERE student_number=? AND account_id<>?', [$number, $id])) {
                field_error('student_number', 'This student number already belongs to another account.');
            }
            $status = choice('academic_status', ['ACTIVE', 'ON_LEAVE', 'GRADUATED', 'WITHDRAWN']);
            if ($old) {
                $student = one('SELECT student_id FROM students WHERE account_id=? FOR UPDATE', [$id]);
                if (!$student) {
                    throw new DomainException('Student profile is missing.');
                }
                query('UPDATE students SET first_name=?,last_name=?,department_id=?,student_number=?,academic_status=? WHERE account_id=?', [$firstName, $lastName, $department, $number, $status, $id]);
            } else {
                query('INSERT INTO students (account_id,department_id,student_number,first_name,last_name,academic_status) VALUES (?,?,?,?,?,?)', [$id, $department, $number, $firstName, $lastName, $status]);
            }
        } elseif ($old) {
            query('UPDATE staff_users SET first_name=?,last_name=?,department_id=?,staff_role=?,access_scope=? WHERE account_id=?', [$firstName, $lastName, $department, $role, $scope, $id]);
        } else {
            query('INSERT INTO staff_users (account_id,department_id,first_name,last_name,staff_role,access_scope) VALUES (?,?,?,?,?,?)', [$id, $department, $firstName, $lastName, $role, $scope]);
        }
        return 'Account saved.';
    });
}
