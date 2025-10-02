@extends( 'layouts.app' ,['editable'=>true])
@section( 'main-content' )
    <p>System</p>
    <div class="flex gap-4">
    @adbm('portal1:zx=50%,zy=200px')
    @adbm('portal2:zx=50%,zy=200px')
    </div>

@endsection