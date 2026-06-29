{{-- FILE: resources/views/admin/layouts/admin.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Admin') — Auto Zenith Parts</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            navy:  '#0A1F5C',
            gold:  '#C8960C',
            'navy-light': '#132474',
          },
          fontFamily: {
            display: ['"Barlow Condensed"','sans-serif'],
            body:    ['"DM Sans"','sans-serif'],
          }
        }
      }
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    body { font-family:'DM Sans',sans-serif; }
    .sidebar-link { display:flex; align-items:center; gap:10px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:500; color:#94a3b8; transition:all .15s; }
    .sidebar-link:hover { background:rgba(255,255,255,.06); color:#fff; }
    .sidebar-link.active { background:rgba(200,150,12,.15); color:#C8960C; }
    .sidebar-link svg { width:18px; height:18px; flex-shrink:0; }
    .stat-card { background:#fff; border:0.5px solid #e2e8f0; border-radius:12px; padding:1.25rem; }
    .badge { display:inline-flex; align-items:center; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:500; }
    .badge-green  { background:#EAF3DE; color:#27500A; }
    .badge-blue   { background:#E6F1FB; color:#0C447C; }
    .badge-amber  { background:#FAEEDA; color:#633806; }
    .badge-red    { background:#FCEBEB; color:#A32D2D; }
    .badge-gray   { background:#F1EFE8; color:#5F5E5A; }
    ::-webkit-scrollbar { width:4px; } ::-webkit-scrollbar-thumb { background:#C8960C; border-radius:2px; }
  </style>
  @stack('head')
</head>
<body class="bg-gray-50">

<div class="flex h-screen overflow-hidden">

  {{-- ── Sidebar ────────────────────────────────────────────────────────── --}}
  <aside class="w-56 bg-navy flex-shrink-0 flex flex-col overflow-y-auto">
    {{-- Logo --}}
    <div class="px-4 py-5 border-b border-white border-opacity-10">
      <div class="font-display font-700 text-white text-lg tracking-wide leading-none">AUTO ZENITH</div>
      <div class="text-gold text-xs font-body font-500 tracking-widest mt-0.5">Admin Panel</div>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 px-2 py-4 space-y-0.5">
      <div class="text-gray-500 text-xs uppercase tracking-widest px-3 py-2 font-body">Main</div>

      <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Dashboard
      </a>

      <div class="text-gray-500 text-xs uppercase tracking-widest px-3 py-2 font-body mt-3">Inventory</div>

      <a href="{{ route('admin.harvest.create') }}" class="sidebar-link {{ request()->routeIs('admin.harvest.create') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        New Harvest
      </a>

      <a href="{{ route('admin.harvest.index') }}" class="sidebar-link {{ request()->routeIs('admin.harvest.index') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Harvest History
      </a>

      <a href="{{ route('admin.inventory.index') }}" class="sidebar-link {{ request()->routeIs('admin.inventory*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        All Inventory
      </a>
      <a href="{{ route('admin.storage.index') }}" class="sidebar-link {{ request()->routeIs('admin.storage*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 21h16.5M4.5 3h15l.75 18h-16.5L4.5 3zM9 3v18M15 3v18M9 9h6M9 15h6"/></svg>
        Storage Rooms
      </a>
      <a href="{{ route('admin.inventory.consumable.create') }}" class="sidebar-link {{ request()->routeIs('admin.inventory.consumable*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3.75H6.912a2.25 2.25 0 00-2.25 2.25v13.5a2.25 2.25 0 002.25 2.25h10.176a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H15M9 3.75c0 .621.504 1.125 1.125 1.125h3.75c.621 0 1.125-.504 1.125-1.125M9 3.75c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125m-5 9 1.5 1.5 3-3.5"/></svg>
        Consumables
      </a>
      <a href="{{ route('parts.compatibility') }}" target="_blank" class="sidebar-link {{ request()->routeIs('parts.compatibility') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Compatibility Checker
      </a>
      <div class="text-gray-500 text-xs uppercase tracking-widest px-3 py-2 font-body mt-3">Orders</div>
      <a href="{{ route('admin.pos.index') }}" class="sidebar-link {{ request()->routeIs('admin.pos*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 6.75a2.25 2.25 0 002.25 2.25h12a2.25 2.25 0 002.25-2.25M3.75 6.75v10.5A2.25 2.25 0 006 19.5h12a2.25 2.25 0 002.25-2.25V6.75M8.25 6.75v-2.25A2.25 2.25 0 0110.5 2.25h3a2.25 2.25 0 012.25 2.25v2.25"/></svg>
        POS Checkout
      </a>
      <a href="{{ route('admin.invoices.index') }}" class="sidebar-link {{ request()->routeIs('admin.invoices*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Invoices/Receipts
      </a>
      <a href="{{ route('admin.invoices.service.create') }}" class="sidebar-link {{ request()->routeIs('admin.invoices.service*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        Quick Receipt
      </a>
      <a href="{{ route('admin.service-rates.index') }}" class="sidebar-link {{ request()->routeIs('admin.service-rates*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.397-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a7.65 7.65 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/></svg>
        Service Rates
      </a>
      <a href="{{ route('admin.customers.index') }}" class="sidebar-link {{ request()->routeIs('admin.customers*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
        Customers & Freelancers
      </a>
      <a href="{{ route('admin.audit.index') }}" class="sidebar-link {{ request()->routeIs('admin.audit*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM14.25 12h.008v.008h-.008V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
        Inventory Audit
      </a>
      <a href="{{ route('admin.returns.index') }}" class="sidebar-link {{ request()->routeIs('admin.returns*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
        Returns
        @php $pendingReturns = \Illuminate\Support\Facades\DB::table('returns')->where('status','pending_inspection')->count(); @endphp
        @if($pendingReturns > 0)
          <span class="ml-auto bg-amber-500 text-white text-xs font-display font-700 w-5 h-5 rounded-full flex items-center justify-center">{{ $pendingReturns }}</span>
        @endif
      </a>
      <a href="{{ route('admin.payments.index') }}" class="sidebar-link {{ request()->routeIs('admin.payments*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 21h16.5a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5H3.75a1.5 1.5 0 00-1.5 1.5v14.25a1.5 1.5 0 001.5 1.5z"/></svg>
        Payments
      </a>
      <a href="{{ route('admin.tabs.index') }}" class="sidebar-link {{ request()->routeIs('admin.tabs*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Open Tabs
      </a>
      <a href="{{ route('admin.tickets.index') }}" class="sidebar-link {{ request()->routeIs('admin.tickets*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/></svg>
        Tickets
        @if(in_array(session('staff_role'), ['admin','manager']))
          @php $pendingTickets = \Illuminate\Support\Facades\DB::table('staff_tickets')->where('status','pending')->count(); @endphp
          @if($pendingTickets > 0)
            <span class="ml-auto bg-amber-500 text-white text-xs font-display font-700 w-5 h-5 rounded-full flex items-center justify-center">{{ $pendingTickets }}</span>
          @endif
        @endif
      </a>
      <a href="{{ route('admin.assets.index') }}" class="sidebar-link {{ request()->routeIs('admin.assets*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l3 3m0 0l-3 3m3-3h-7.5M6 7.5h3v3H6v-3z"/></svg>
        Assets & Equipment
      </a>
      @if(session('staff_role') === 'admin')
      <a href="{{ route('admin.part-names.index') }}" class="sidebar-link {{ request()->routeIs('admin.part-names*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/></svg>
        Part Names Manager
      </a>
      @endif
      <a href="{{ route('admin.transfers.index') }}" class="sidebar-link {{ request()->routeIs('admin.transfers*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3m4 8l-4-4m0 0l4-4m-4 4h18"/></svg>
        Stock Transfers
      </a>
      <a href="{{ route('admin.reports.financial') }}" class="sidebar-link {{ request()->routeIs('admin.reports.financial') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
        Financial Reports
      </a>
      <a href="{{ route('admin.orders.place.create') }}" class="sidebar-link {{ request()->routeIs('admin.orders.place*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
        Place Order
      </a>
      <a href="{{ route('admin.orders.index') }}" class="sidebar-link {{ request()->routeIs('admin.orders.index') || (request()->routeIs('admin.orders.show') ) ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        Orders
        @php $pending = \Illuminate\Support\Facades\DB::table('orders')->whereIn('payment_status',['pending','transfer_sent'])->count(); @endphp
        @if($pending > 0)
          <span class="ml-auto bg-red-500 text-white text-xs font-display font-700 w-5 h-5 rounded-full flex items-center justify-center">{{ $pending }}</span>
        @endif
      </a>

      @if(in_array(session('staff_role'), ['admin','manager']))
      <div class="text-gray-500 text-xs uppercase tracking-widest px-3 py-2 font-body mt-3">Admin</div>
      <a href="{{ route('admin.staff.index') }}" class="sidebar-link {{ request()->routeIs('admin.staff*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Staff
      </a>
      @endif
    </nav>

    {{-- Staff info --}}
    <div class="px-4 py-4 border-t border-white border-opacity-10">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 bg-gold rounded-full flex items-center justify-center flex-shrink-0">
          <span class="font-display font-700 text-navy text-sm">{{ substr(session('staff_name','?'),0,1) }}</span>
        </div>
        <div class="min-w-0">
          <div class="text-white text-xs font-body font-500 truncate">{{ session('staff_name') }}</div>
          <div class="text-gold text-xs font-body uppercase tracking-wider">{{ session('staff_role') }}</div>
        </div>
      </div>
      <a href="{{ route('admin.logout') }}" class="mt-3 flex items-center gap-2 text-xs text-gray-500 hover:text-white font-body transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Log out
      </a>
    </div>
  </aside>

  {{-- ── Main content ───────────────────────────────────────────────────── --}}
  <div class="flex-1 flex flex-col overflow-hidden">

    {{-- Top bar --}}
    <header class="bg-white border-b border-gray-200 px-6 py-3.5 flex items-center justify-between flex-shrink-0">
      <div>
        <h1 class="font-display font-700 text-navy text-xl tracking-wide">@yield('page-title','Dashboard')</h1>
        @hasSection('page-sub')
          <p class="text-xs text-gray-400 font-body mt-0.5">@yield('page-sub')</p>
        @endif
      </div>
      <div class="flex items-center gap-3">
        @yield('header-actions')
        <a href="{{ route('admin.harvest.create') }}"
           class="bg-navy text-white font-display font-700 text-xs px-4 py-2 rounded-xl tracking-wide hover:bg-navy-light transition-colors flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
          New Harvest
        </a>
      </div>
    </header>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border-b border-green-200 px-6 py-2.5 text-sm text-green-700 font-body flex items-center gap-2">
      <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border-b border-red-200 px-6 py-2.5 text-sm text-red-700 font-body">{{ session('error') }}</div>
    @endif

    {{-- Page body --}}
    <main class="flex-1 overflow-y-auto p-6">
      @yield('content')
    </main>
  </div>
</div>

@stack('scripts')
</body>
</html>
