<?php
declare(strict_types=1);
use App\Core\Database\Database;
/** @var Database $db */$db=$app->make(Database::class);
$tables=$db->execute("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN('purchase_orders','purchase_order_lines')")->fetch_assoc();
if((int)$tables['n']!==2)throw new RuntimeException('Purchase order tables missing.');
$permissions=$db->execute("SELECT COUNT(*) n FROM permissions WHERE module='purchase_orders'")->fetch_assoc();
if((int)$permissions['n']!==5)throw new RuntimeException('Purchase order permissions missing.');
$assigned=$db->execute("SELECT COUNT(*) n FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id WHERE r.code='super_admin' AND p.module='purchase_orders'")->fetch_assoc();
if((int)$assigned['n']!==5)throw new RuntimeException('Purchase order permissions not assigned.');
$column=$db->execute("SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='purchase_order_lines' AND COLUMN_NAME='line_total'")->fetch_assoc();
if(!str_contains(strtolower((string)($column['EXTRA']??'')),'generated'))throw new RuntimeException('Purchase order line total must be database generated.');
