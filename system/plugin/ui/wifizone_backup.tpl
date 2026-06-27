{include file="sections/header.tpl"}
<section class="content-header"><h1>Sauvegardes WifiZone</h1></section>
<section class="content">
    <div class="box box-primary"><div class="box-body">
        <form method="post" class="form-inline">{csrf_field()}
            <input type="password" name="password" class="form-control" placeholder="Mot de passe chiffrement">
            <button type="submit" name="create_backup" value="1" class="btn btn-danger">Sauvegarde AES complète</button>
            <button type="submit" name="export_config" value="1" class="btn btn-info">Export config seule</button>
        </form>
        <hr>
        <form method="post">{csrf_field()}
            <select name="job_type" class="form-control"><option value="full">Complète</option><option value="config">Config</option></select>
            <input type="datetime-local" name="scheduled_at" class="form-control">
            <input type="text" name="remote_target" class="form-control" placeholder="s3:// ou gdrive://">
            <button type="submit" name="schedule" value="1" class="btn btn-default">Planifier</button>
        </form>
    </div></div>
    <table class="table table-striped"><thead><tr><th>ID</th><th>Type</th><th>Statut</th><th>Fichier</th><th>Planifié</th></tr></thead>
    <tbody>{foreach $jobs as $j}<tr><td>{$j.id}</td><td>{$j.job_type}</td><td>{$j.status}</td><td>{$j.file_path}</td><td>{$j.scheduled_at}</td></tr>{/foreach}</tbody></table>
</section>
{include file="sections/footer.tpl"}
