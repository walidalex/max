<?php
declare(strict_types=1);
use App\Core\Database\Database;
/** @var Database $db */$db=$app->make(Database::class);
$tables=$db->execute('SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN(\'accounts\',\'employees\',\'accounting_periods\',\'journal_entries\',\'journal_lines\',\'accounting_setup\',\'employee_custodies\',\'employee_custody_settlements\')')->fetch_assoc();if((int)$tables['n']!==8)throw new RuntimeException('Accounting foundation tables are missing.');
$accounts=$db->execute('SELECT COUNT(*) n FROM accounts')->fetch_assoc();if((int)$accounts['n']!==76)throw new RuntimeException('Seed chart of accounts is incomplete.');
$bad=$db->execute('SELECT COUNT(*) n FROM accounts WHERE account_code NOT REGEXP \'^[0-9]{6}$\' OR (parent_id IS NULL AND is_postable=1)')->fetch_assoc();if((int)$bad['n']!==0)throw new RuntimeException('Account code or root posting invariant failed.');
$permissions=$db->execute('SELECT COUNT(*) n FROM permissions WHERE module IN(\'accounts\',\'journal_entries\',\'accounting_periods\',\'accounting_setup\',\'trial_balance\',\'employees\',\'employee_custodies\')')->fetch_assoc();if((int)$permissions['n']!==19)throw new RuntimeException('Accounting permissions are incomplete.');
$assigned=$db->execute('SELECT COUNT(*) n FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id WHERE r.code=\'super_admin\' AND p.module IN(\'accounts\',\'journal_entries\',\'accounting_periods\',\'accounting_setup\',\'trial_balance\',\'employees\',\'employee_custodies\')')->fetch_assoc();if((int)$assigned['n']!==19)throw new RuntimeException('Accounting permissions are not assigned to super_admin.');
