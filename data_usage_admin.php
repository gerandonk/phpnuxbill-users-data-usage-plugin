<?php

register_menu("UserDataUsage", true, "UserDataUsageAdmin", 'RADIUS', 'fa fa-download');

function UserDataUsageAdmin()
{
    global $ui;
    _admin();
    $ui->assign('_title', 'DataUsage');
    $ui->assign('_system_menu', '');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);
    $search = $_POST['q'] ?? '';
    $page = !isset($_GET['page']) ? 1 : (int)$_GET['page'];
    $perPage = 10;

    $data = fetch_user_in_out_data_admin($search, $page, $perPage);
    $total = count_user_in_out_data_admin($search);
    $pagination = create_pagination_admin($page, $perPage, $total);

    $ui->assign('q', $search);
    $ui->assign('data', $data);
    $ui->assign('pagination', $pagination);
    $ui->display('data_usage_admin.tpl');
}

function fetch_user_in_out_data_admin($search = '', $page = 1, $perPage = 10)
{
    $table = isMysqlRadius() ? 'radacct' : 'rad_acct';
    $query = ORM::for_table($table)->where_not_equal('acctoutputoctets', '0');

    if ($search) {
        $query->where_like('username', '%' . $search . '%');
    }

    $query->limit($perPage)->offset(($page - 1) * $perPage);
    $data = $query->find_many();

    foreach ($data as &$row) {
        // Perform sum first, then convert
        $totalBytes = $row->acctoutputoctets + $row->acctinputoctets;

        $row->acctoutputoctets = convert_bytes_admin($row->acctoutputoctets);
        $row->acctinputoctets = convert_bytes_admin($row->acctinputoctets);
        $row->totalBytes = convert_bytes_admin($totalBytes);

        $lastRecord = ORM::for_table($table)
            ->where('username', $row->username)
            ->order_by_desc(isMysqlRadius() ? 'acctstoptime' : 'acctstatustype')
            ->find_one();

        if ($lastRecord && $lastRecord->acctstatustype == 'Start') {
            $row->status = '<span class="badge btn-success">Connected</span>';
        } else {
            $row->status = '<span class="badge btn-danger">Disconnected</span>';
        }
    }

    return $data;
}

function count_user_in_out_data_admin($search = '')
{
    $table = isMysqlRadius() ? 'radacct' : 'rad_acct';
    $query = ORM::for_table($table)->where_not_equal('acctoutputoctets', '0');

    if ($search) {
        $query->where_like('username', '%' . $search . '%');
    }

    return $query->count();
}

function create_pagination_admin($page, $perPage, $total)
{
    $pages = ceil($total / $perPage);
    return [
        'current' => $page,
        'total' => $pages,
        'previous' => ($page > 1) ? $page - 1 : null,
        'next' => ($page < $pages) ? $page + 1 : null,
    ];
}

function convert_bytes_admin($bytes)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}
