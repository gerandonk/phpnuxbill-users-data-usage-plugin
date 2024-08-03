<?php

register_menu("User In/Out Data", true, "user_in_out_ui", 'RADIUS', '');

function user_in_out_ui()
{
    global $ui;
    _admin();
    $ui->assign('_title', 'User In/Out Data');
    $ui->assign('_system_menu', 'radius');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);

    $search = $_POST['q'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 10;  // Change the number of items per page if needed

    $data = fetch_user_in_out_data($search, $page, $perPage);
    $total = count_user_in_out_data($search);
    $pagination = create_pagination($page, $perPage, $total);

    $ui->assign('q', $search);
    $ui->assign('data', $data);
    $ui->assign('pagination', $pagination);
    $ui->display('inout.tpl');
}

function fetch_user_in_out_data($search = '', $page = 1, $perPage = 100)
{
    $query = ORM::for_table('rad_acct')->where_not_equal('acctOutputOctets', '0');
    if ($search) {
        $query->where_like('username', '%' . $search . '%');
    }

    $query->limit($perPage)->offset(($page - 1) * $perPage);
    $data = Paginator::findMany($query, [], $perPage);

    foreach ($data as &$row) {
        $row->acctOutputOctets = convert_bytes($row->acctOutputOctets);
        $row->acctInputOctets = convert_bytes($row->acctInputOctets);
        $row->totalBytes = convert_bytes($row->acctOutputOctets + $row->acctInputOctets);

        $lastRecord = ORM::for_table('rad_acct')
            ->where('username', $row->username)
            ->order_by_desc('acctstatustype')
            ->find_one();

        if ($lastRecord && $lastRecord->acctstatustype == 'Start') {
            $row->status = '<span class="badge btn-success">Connected</span>';
        } else {
            $row->status = '<span class="badge btn-danger">Disconnected</span>';
        }
    }

    return $data;
}

function count_user_in_out_data($search = '')
{
    $query = ORM::for_table('rad_acct')->where_not_equal('acctOutputOctets', '0');
    if ($search) {
        $query->where_like('username', '%' . $search . '%');
    }
    return $query->count();
}

function create_pagination($page, $perPage, $total)
{
    $pages = ceil($total / $perPage);
    $pagination = [
        'current' => $page,
        'total' => $pages,
        'previous' => ($page > 1) ? $page - 1 : null,
        'next' => ($page < $pages) ? $page + 1 : null,
    ];
    return $pagination;
}

function convert_bytes($bytes)
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
