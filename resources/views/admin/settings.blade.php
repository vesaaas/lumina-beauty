@extends('admin.layout')

@section('title', 'Settings - Lumina Beauty Admin')
@section('heading', 'Settings')
@section('eyebrow', 'Project setup')

@section('content')
  <section class="admin-panel">
    <div class="panel-heading"><h2>Application Settings</h2></div>
    <table class="admin-table">
      <tbody>
        <tr><th>App</th><td>{{ config('app.name') }}</td></tr>
        <tr><th>Database</th><td>{{ config('database.default') }} on {{ config('database.connections.mysql.host') }}</td></tr>
        <tr><th>Product uploads</th><td><code>storage/app/public/products</code>, served through <code>public/storage</code></td></tr>
        <tr><th>Admin creation</th><td>Only through the seeder. There is no public add-admin feature.</td></tr>
      </tbody>
    </table>
  </section>
@endsection
