@extends('admin.layout')

@section('title', 'Customers - Lumina Beauty Admin')
@section('heading', 'Customers')
@section('eyebrow', 'User accounts')

@section('content')
  <section class="admin-panel">
    <div class="panel-heading"><h2>Registered Users</h2></div>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Orders</th><th>Joined</th></tr></thead>
      <tbody>
        @foreach ($users as $user)
          <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td><span class="status-pill">{{ $user->is_admin ? 'admin' : 'customer' }}</span></td>
            <td>{{ $user->orders_count }}</td>
            <td>{{ $user->created_at->format('d M Y') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <div class="pagination">{{ $users->links() }}</div>
  </section>
@endsection
