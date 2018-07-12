@servers(['web' => 'root@cad-proxmox-dashboard.rit.edu'])

@setup
    $url = 'https://cad-proxmox-dashboard.cias.rit.edu';
    $repository = 'git@gitlab.cad.rit.edu:cadtech-support/proxmox-dashboard.git';
    $releases_dir = '/var/www/releases';
    $app_dir = '/var/www/html';
    $storage_dir = '/var/www/storage';
    $env_file = '/var/www/.env';
    $release = date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release;
@endsetup

@story('deploy')
    clone_repository
    run_composer
    update_symlinks
    clean_old_releases
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    GIT_SSH_COMMAND="ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no" git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
@endtask

@task('run_composer')
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    export https_proxy=http://cias-http-proxy.rit.edu:3128; composer install --prefer-dist --no-scripts -q -o
@endtask

@task('update_symlinks')
    echo "Linking storage directory"
    rm -rf {{ $new_release_dir }}/storage
    ln -nfs {{ $storage_dir }} {{ $new_release_dir }}/storage

    echo 'Linking .env file'
    ln -nfs {{ $env_file }} {{ $new_release_dir }}/.env

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }}/public {{ $app_dir }}


    echo 'Spamming cache reset URL'

    curl -sL -X POST {{ $url }}/deploymentHook > /dev/null
    curl -sL -X POST {{ $url }}/deploymentHook > /dev/null
    curl -sL -X POST {{ $url }}/deploymentHook > /dev/null
    curl -sL -X POST {{ $url }}/deploymentHook > /dev/null

@endtask

@task('clean_old_releases')
    # This lists our releases by modification time and delete all but the 3 most recent.
    purging=$(ls -dt {{ $releases_dir }}/* | tail -n +3);

    if [ "$purging" != "" ]; then
        echo Purging old releases: $purging;
    rm -rf $purging;
        else
        echo "No releases found for purging at this time";
    fi
@endtask
