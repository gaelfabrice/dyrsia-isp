{include file="sections/header.tpl"}
<style>
    /* Styles for overall layout and responsiveness */
    body {
        background-color: #f8f9fa;
        font-family: 'Arial', sans-serif;
        padding: 0;
        margin: 0;
    }

    .container {
        margin-top: 20px;
        background-color: #d8dfe5;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        max-width: 98%;
        overflow-x: auto;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }

    /* Styles for table and pagination */
    .table {
        width: 100%;
        margin-bottom: 1rem;
        background-color: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .table th {
        vertical-align: middle;
        border-color: #dee2e6;
        background-color: #343a40;
        color: #fff;
    }

    .table td {
        vertical-align: middle;
        border-color: #dee2e6;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.075);
        color: #333;
        font-weight: bold;
        transition: background-color 0.3s, color 0.3s;
    }

    .pagination .page-item .page-link {
        color: #007bff;
        background-color: #fff;
        border: 1px solid #dee2e6;
        margin: 0 2px;
        padding: 6px 12px;
        transition: background-color 0.3s, color 0.3s;
    }

    .pagination .page-item .page-link:hover {
        background-color: #e9ecef;
        color: #0056b3;
    }

    .pagination .page-item.active .page-link {
        z-index: 1;
        color: #fff;
        background-color: #007bff;
        border-color: #007bff;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        display: inline-block;
        padding: 5px 10px;
        margin-right: 5px;
        border: 1px solid #ccc;
        background-color: #fff;
        color: #333;
        cursor: pointer;
    }

    .hidden-field {
        display: none;
    }
</style>
<style>
    .btn-group-flex {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-group-flex .btn {
        flex: 1 1 auto;
        /* Allow buttons to shrink/grow as needed */
        max-width: 150px;
        /* Optional: Limit button width */
    }
</style>

<div class="container">
    {if $activityLog|@count > 0}
    <table id="detailsTable" class="table table-bordered table-striped table-condensed">
        <thead>
            <tr>
                <th>{Lang::T('Type')}</th>
                <th>{Lang::T('Details')}</th>
                <th>{Lang::T('User')}</th>
                <th>{Lang::T('Timestamp')}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$activityLog item=log}
            <tr>
                <td>{$log.type}</td>
                <td>{$log.details}</td>
                <td>{$log.user}</td>
                <td>{$log.timestamp}</td>
            </tr>
            {/foreach}
        </tbody>
    </table>
    {else}
    <p class="no-activity" style="text-align: center;">{Lang::T('No activity logs found for this token.')}</p>
    {/if}
</div><br>
<div class="panel-body">
    <div class="form-group">
        <a href="javascript:history.back()" class="btn btn-success btn-block" name="save" value="save" type="submit">
            {Lang::T('Go Back')}
        </a>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>

<script>
    const $q = jQuery.noConflict();

    $q(document).ready(function () {
        $q('#detailsTable').DataTable({
            "pagingType": "full_numbers",
            "order": [
                [1, 'desc']
            ]
        });
    });
</script>
{include file="sections/footer.tpl"}