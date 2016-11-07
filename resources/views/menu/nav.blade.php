

<li {{ (Request::is('/') ? 'class=active' : '') }}><a href="{{ route('dashboard') }}"><i class="fa fa-dashboard"></i> Dashboard <span class="sr-only">(current)</span></a></li>
<li {{ (Request::is('provision') ? 'class=active' : '') }}><a href="{{ route('provision') }}"><i class="fa fa-desktop"></i> Provision</a></li>
<li {{ (Request::is('tasks') ? 'class=active' : '') }}><a href="{{ route('tasks') }}"><i class="fa fa-list"></i> Tasks</a></li>
<li {{ (Request::is('config') ? 'class=active' : '') }}><a href="{{ route('config') }}"><i class="fa fa-gears"></i> Configuration</a></li>