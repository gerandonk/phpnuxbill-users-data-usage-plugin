<?php

register_menu("User Data Usage", false, "UserDataUsage", 'AFTER_DASHBOARD',
    'fa fa-bar-chart');

function UserDataUsage()
{
    global $ui;
    $ui->assign('_title', 'DataUsage');
    $ui->assign('_system_menu', '');
    $user = User::_info();
    $ui->assign('_user', $user);
    $search = $user['username'];
    $page = !isset($_GET['page']) ? 1 : (int)$_GET['page'];
    $perPage = 10;

    $data = fetch_user_in_out_data($search, $page, $perPage);
    $total = count_user_in_out_data($search);
    $pagination = create_pagination($page, $perPage, $total);

    $ui->assign('q', $search);
    $ui->assign('data', $data);
    $ui->assign('pagination', $pagination);
    $ui->display('data_usage_user.tpl');
}

function fetch_user_in_out_data($search = '', $page = 1, $perPage = 10)
{

    $query = ORM::for_table('rad_acct');
    if ($search) {
        $query->where_like('username', '%' . $search . '%');
    }

    $query->limit($perPage)->offset(($page - 1) * $perPage);
    $data = Paginator::findMany($query, [], $perPage);

    foreach ($data as &$row) {
        $row->acctOutputOctets = convert_bytes($row->acctoutputoctets);
        $row->acctInputOctets = convert_bytes($row->acctinputoctets);
        $row->totalBytes = convert_bytes($row->acctoutputoctets + $row->acctinputoctets);

        if(isMysqlRadius()) {
            $lastRecord = ORM::for_table('radacct')
                ->where('username', $row->username)
                ->order_by_desc('acctstoptime')
                ->find_one();
        } else {
            $lastRecord = ORM::for_table('rad_acct')
                ->where('username', $row->username)
                ->order_by_desc('acctstatustype')
                ->find_one();
        }

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
    $query = ORM::for_table('rad_acct')->where_not_equal('acctoutputoctets', '0');
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
