<div class="modal fade" id="editValueModal" tabindex="-1" role="dialog" aria-labelledby="editValueModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editValueModalLabel">Chỉnh sửa Giá trị</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            {{-- Form sẽ được cập nhật ACTION URL bằng JS khi nút "Sửa" được click --}}
            <form action="#" method="POST">
                @csrf
                @method('PUT') 
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_valueName">Tên Giá trị mới</label>
                        <input type="text" class="form-control" id="edit_valueName" name="valueName" required maxlength="100">
                    </div>
                    {{-- Hiển thị lỗi validation nếu có --}}
                    @error('valueName')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>