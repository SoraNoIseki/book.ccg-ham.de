<form method="POST">
    @csrf

    @if ($errors->any())
        <div class="mb-5 bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4" role="alert">
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="mb-5 -mx-3 flex flex-wrap">
        <div class="w-full px-3 sm:w-1/2">
            <div class="mb-5">
                <label for="date" class="mb-3 block text-base font-medium text-[#07074D]">
                    日期
                </label>
                <input type="date" id="date" disabled value="{{ $date }}"
                    class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md" />
                <input type="hidden" name="date" id="dateHidden" value="{{ $date }}">
            </div>
        </div>
    </div>

    <div class="mb-5">
        <label for="pray" class="mb-3 block text-base font-medium text-[#07074D]">
            代祷（#1代祷、#2姐妹团契1、#3姐妹团契2）
        </label>
        <textarea rows="10" name="pray" id="pray" placeholder="Type the pray texts" 
            class="w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md">@isset($content['pray']){{ $content['pray'] }}@endisset</textarea>
    </div>

    <div class="mb-5">
        <label for="preach" class="mb-3 block text-base font-medium text-[#07074D]">
            经文（宣召、启应、读经）
        </label>
        <textarea rows="10" name="preach" id="preach" placeholder="Type the preach texts"
            class="w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md">@isset($content['preach']){{ $content['preach'] }}@endisset</textarea>
    </div>

    <div class="mb-5">
        <label for="report" class="mb-3 block text-base font-medium text-[#07074D]">
            通讯报告
        </label>
        <textarea rows="10" name="report" id="report" placeholder="Type the report texts"
            class="w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md">@isset($content['report']){{ $content['report'] }}@endisset</textarea>
    </div>

    <div class="mb-5">
        <label for="scripture" class="mb-3 block text-base font-medium text-[#07074D]">
            讲道
        </label>
        <textarea rows="10" name="scripture" id="scripture" placeholder="Type the scripture texts"
            class="w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md">@isset($content['scripture']){{ $content['scripture'] }}@endisset</textarea>
    </div>

    <div class="mb-5">
        <label for="song" class="mb-3 block text-base font-medium text-[#07074D]">
            诗歌（三首敬拜+回应诗歌）
        </label>
        <textarea rows="10" name="song" id="song" placeholder="Type the song texts"
            class="w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md">@isset($content['song']){{ $content['song'] }}@endisset</textarea>
    </div>

    <div class="mb-5">
        <label for="worker" class="mb-3 block text-base font-medium text-[#07074D]">
            服事列表
        </label>
        <textarea rows="10" name="worker" id="worker" placeholder="Type the worker texts"
            class="w-full resize-none rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md">@isset($content['worker']){{ $content['worker'] }}@endisset</textarea>
    </div>

    <div>
        <button type="submit" formaction="{{ route('book-group.ppt.store') }}"
            class="hover:shadow-form rounded-md bg-[#6A64F1] py-3 px-8 text-center text-base font-semibold text-white outline-none">
            保存
        </button>
        <button type="submit" formaction="{{ route('book-group.ppt.download') }}"
            class="hover:shadow-form rounded-md bg-[#c88437] py-3 px-8 text-center text-base font-semibold text-white outline-none">
            保存并下载
        </button>
    </div>
</form>
