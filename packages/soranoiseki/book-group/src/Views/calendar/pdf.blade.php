<x-pdf-layout>
    <!--<link href="{{ asset('css/calendar.css') }}" rel="stylesheet">-->
    <!--<link href="{{ ltrim(public_path('css/calendar.css'), '/') }}" rel="stylesheet">-->


    <x-slot name="header">
        <h2 class="font-semibold text-xl text-main dark:text-gray-200 leading-tight">
            Powerpoint
        </h2>
    </x-slot>

    <div id="mainContent">
        <div class="calendar cover">
            <div class="calendar-cover">
                <div class="image">
                    @php
                        //$url_image = "images/calendar/" . $calendar['year'] . "/cover.jpg";
                    @endphp
                    {{-- <img src="{{ route('file.id', '3') }}" /> --}}
                </div>
            </div>
        </div>
        @foreach(range(1, 12) as $month)
            <div class="calendar month-item month-item-{{$month}}">
                <div class="calendar-header">
                    <div class="outer-wrapper">
                        <div class="inner-wrapper">
                            @php
                                // $url_image = "images/calendar/" . $details[$month]['year'] . "/" . $details[$month]['image'];
                                $url_image = "images/calendar/" . $year . "/" . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . ".jpg";
                            @endphp
                            <div class="image" class="image-{{$calendar['year']}}-{{$month}}" style="background-image: url('{{ url("$url_image") }}');"></div>
                            <div class="overlay">
                                <div class="prev-month header-month">
                                    <div class="label">
                                        <span class="zh">{{$days[$month-1][15]['details']['gregorian_year']}}年{{(int)$days[$month-1][15]['details']['gregorian_month']}}月</span>
                                        <span class="de">{{ $monthText[(int)$days[$month-1][15]['details']['gregorian_month'] - 1] }}</span>
                                    </div>
                                    <ul class="weekdays">
                                        <li class="weekday-item workday">Mo</li>
                                        <li class="weekday-item workday">Di</li>
                                        <li class="weekday-item workday">Mi</li>
                                        <li class="weekday-item workday">Do</li>
                                        <li class="weekday-item workday">Fr</li>
                                        <li class="weekday-item weekend color-red">Sa</li>
                                        <li class="weekday-item weekend color-red">So</li>
                                    </ul>
                                    <ul class="days">
                                        @foreach ($days[$month-1] as $day)
                                            <li class="day-item {{ $day['class'] }}">
                                                <div class="day @if($day['data']['holiday'] || $day['details']['week_no'] == 6 || $day['details']['week_no'] == 0) color-red @endif">
                                                    {{ $day['day'] }}
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="next-month header-month">
                                    <div class="label">
                                        <span class="zh">{{$days[$month+1][15]['details']['gregorian_year']}}年{{(int)$days[$month+1][15]['details']['gregorian_month']}}月</span>
                                        <span class="de">{{ $monthText[(int)$days[$month+1][15]['details']['gregorian_month'] - 1] }}</span>
                                    </div>
                                    <ul class="weekdays">
                                        <li class="weekday-item workday">Mo</li>
                                        <li class="weekday-item workday">Di</li>
                                        <li class="weekday-item workday">Mi</li>
                                        <li class="weekday-item workday">Do</li>
                                        <li class="weekday-item workday">Fr</li>
                                        <li class="weekday-item weekend color-red">Sa</li>
                                        <li class="weekday-item weekend color-red">So</li>
                                    </ul>
                                    <ul class="days">
                                        @foreach ($days[$month+1] as $day)
                                            <li class="day-item {{ $day['class'] }}">
                                                <div class="day @if($day['data']['holiday'] || $day['details']['week_no'] == 6 || $day['details']['week_no'] == 0) color-red @endif">
                                                    {{ $day['day'] }}
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="calendar-content">
                    <div class="calendar-content-header">
                        <div class="year">
                            <span class="zh">{{ $calendar['year'] }}年{{ $month }}月</span>
                            <span class="de">{{ $monthText[$month - 1] }}</span>
                        </div>

                        @if($details)
                            <div class="bible-verse">
                                <div class="bible-verse-wrapper">
                                    <div class="bible-verse-text">{!! str_replace('|', '<br>', $details[$month]['bible_text']) !!}</div>
                                    <div class="bible-verse-source">—— {{ $details[$month]['bible_source'] }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="calendar-weekdays">
                        <ul class="weekdays">
                            <li class="weekday-item workday">Montag 一</li>
                            <li class="weekday-item workday">Dienstag 二</li>
                            <li class="weekday-item workday">Mittwoch 三</li>
                            <li class="weekday-item workday">Donnerstag 四</li>
                            <li class="weekday-item workday">Freitag 五</li>
                            <li class="weekday-item weekend">Samstag 六</li>
                            <li class="weekday-item weekend">Sonntag 日</li>
                        </ul>
                    </div>
                    <div class="calendar-days">
                        <ul class="days">
                            @foreach ($days[$month] as $day)
                                <li class="day-item day-item-{{$day['date']}} {{ $day['class'] }}">
                                    <div class="day @if($day['data']['holiday'] || $day['details']['week_no'] == 6 || $day['details']['week_no'] == 0) color-red @endif">
                                        {{ $day['day'] }}
                                    </div>
                                    @if($day['details'])
                                        <div class="lunar-day">
                                            @if($day['details']['term'])
                                                {{ $day['details']['term'] }}
                                            @else
                                                @if((int)$day['details']['lunar_day'] == 1)
                                                    {{ $day['details']['lunar_month_chinese'] }}
                                                @else
                                                    {{ $day['details']['lunar_day_chinese'] }}
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                    @if($day['data']['holiday'])
                                        <div class="holiday color-red">
                                            {{ $day['data']['holiday']['name_cn'] }}
                                        </div>
                                    @endif
                                    @if($day['data']['events'])
                                        <div class="events">
                                            @foreach($day['data']['events'] as $event)
                                                <div class="event event-{{$event['type']}}">
                                                    {!! $event['name'] !!}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="calendar-footer">
                    <div class="image">
                        @php
                            // $url_image = "images/calendar/" . $details[$month]['year'] . "/footer.jpg";
                            $url_image = "images/calendar/" . $year . "/footer.jpg";
                        @endphp
                        <img src={{ url("$url_image") }} />
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-pdf-layout>