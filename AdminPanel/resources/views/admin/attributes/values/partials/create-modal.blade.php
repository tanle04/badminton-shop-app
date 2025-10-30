<div class="modal fade" id="createValueModal" tabindex="-1" role="dialog" aria-labelledby="createValueModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createValueModalLabel">Thêm Giá trị mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            {{-- Form gửi đến ProductAttributeController@storeValue --}}
            <form action="{{ route('admin.attributes.values.store', $attributeID) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="valueName">Tên Giá trị (<small>VD: 36, L, G5</small>)</label>
                        <input type="text" class="form-control" id="valueName" name="valueName" required maxlength="100">
                    </div>
                    {{-- Hiển thị lỗi validation nếu có --}}
                    @error('valueName')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu Giá trị</button>
                </div>
            </form>
        </div>
    </div>
</div>