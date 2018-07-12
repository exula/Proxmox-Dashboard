<li class="nav-item {{ (Request::is('/') ? 'active' : '') }}"><a href="{{ route('dashboard') }}" class="nav-link"><i class="fa fa-dashboard"></i> Dashboard <span class="sr-only">(current)</span></a></li>
<li class="nav-item {{ (Request::is('provision') ? 'active' : '') }}"><a href="{{ route('provision') }}" class="nav-link"><i class="fa fa-desktop"></i> Provision</a></li>
<li class="nav-item {{ Request::is('virtualmachines' ? 'active' : '') }}"><a href="{{ route('virtualmachines') }}" class="nav-link"><i class="fa fa-space-shuttle"></i> Virtual Machines</a></li>
<li class="nav-item {{ (Request::is('tasks') ? 'active' : '') }}"><a href="{{ route('tasks') }}" class="nav-link"><i class="fa fa-list"></i> Tasks</a></li>
