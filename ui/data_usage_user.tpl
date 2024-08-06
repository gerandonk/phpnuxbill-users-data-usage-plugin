{include file="sections/user-header.tpl"}
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                User In/Out Data
            </div>
            <div class="table-responsive">
                <table id="datatable" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Download</th>
                        <th>Upload</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $data as $row}
                        <tr>
                            <td>{$row.id}</td>
                            <td>{$row.acctInputOctets}</td>
                            <td>{$row.acctOutputOctets}</td>
                            <td>{$row.totalBytes}</td>
                            <td>{$row.status}</td>
                            <td>{$row.dateAdded}</td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
            {include file="pagination.tpl"}
        </div>
    </div>
</div>
{include file="sections/user-footer.tpl"}
