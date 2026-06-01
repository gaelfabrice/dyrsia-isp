{include file="sections/header.tpl"}

<div class="row">
	<div class="col-sm-12">
		<div class="panel panel-hovered mb20 panel-primary">
			<div class="panel-heading">{Lang::T('Bandwidth Plans')}</div>

			<div class="panel-body">

				<div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
					<div class="col-md-8">
						<form id="site-search" method="post" action="{Text::url('bandwidth/list/')}">
							<div class="input-group">
								<div class="input-group-addon">
									<span class="fa fa-search"></span>
								</div>
								<input type="text" name="name" class="form-control"
									placeholder="{Lang::T('Search by Name')}...">
								<div class="input-group-btn">
									<button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
								</div>
							</div>
						</form>
					</div>

					<div class="col-md-4">
						<a href="{Text::url('bandwidth/add')}" class="btn btn-primary btn-block">
							<i class="ion ion-android-add"></i> {Lang::T('New Bandwidth')}
						</a>
					</div>
					&nbsp;
				</div>

				<!-- TOP RIGHT DELETE BUTTON -->
				<div style="display:flex; justify-content:flex-end; margin-bottom:10px;">
					<form method="post" action="{Text::url('bandwidth/bulk-delete')}">

						<button type="submit" class="btn btn-danger btn-sm"
							onclick="return confirm('Delete selected items?')">
							<i class="glyphicon glyphicon-trash"></i> Delete Selected
						</button>

				</div>

				<div class="table-responsive">
					<table class="table table-bordered table-condensed table-striped table_mobile">
						<thead>
							<tr>
								<th width="40">
									<input type="checkbox" id="checkAll">
								</th>
								<th>{Lang::T('Bandwidth Name')}</th>
								<th>{Lang::T('Rate')}</th>
								<th>Burst</th>
								<th>{Lang::T('Manage')}</th>
							</tr>
						</thead>

						<tbody>
							{foreach $d as $ds}
								<tr>
									<td>
										<input type="checkbox" name="ids[]" value="{$ds['id']}" class="checkItem">
									</td>

									<td>{$ds['name_bw']}</td>

									<td>
										{$ds['rate_down']} {$ds['rate_down_unit']} /
										{$ds['rate_up']} {$ds['rate_up_unit']}
									</td>

									<td>{$ds['burst']}</td>

									<td>
										<a href="{Text::url('bandwidth/edit/', $ds['id'])}"
											class="btn btn-sm btn-warning">{Lang::T('Edit')}</a>
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
				</div>

				</form>

				{include file="pagination.tpl"}

				<div class="bs-callout bs-callout-info" id="callout-navbar-role">
					<h4>{Lang::T('Create Bandwidth Package for expired Internet Package')}</h4>
					<p>{Lang::T('When customer expired, you can move it to Expired Internet Package')}</p>
				</div>

			</div>
		</div>
	</div>
</div>

<!-- SELECT ALL SCRIPT -->
<script>
document.getElementById('checkAll').onclick = function () {
	let items = document.getElementsByClassName('checkItem');
	for (let i = 0; i < items.length; i++) {
		items[i].checked = this.checked;
	}
};
</script>

{include file="sections/footer.tpl"}