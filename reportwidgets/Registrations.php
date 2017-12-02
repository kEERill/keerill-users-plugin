<?php namespace KEERill\Users\ReportWidgets;

use Lang;
use Carbon\Carbon;
use ApplicationException;
use KEERill\Users\Models\User;
use Backend\Classes\ReportWidgetBase;

class Registrations extends ReportWidgetBase
{
    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'backend::lang.dashboard.widget_title_label',
                'default'           => 'New registrations',
                'type'              => 'string',
                'validationPattern' => '^.+$'
            ],
            'days' => [
                'title'             => 'keerill.users::lang.report.num_days',
                'default'           => '7',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$'
            ]
        ];
    }

    public function render()
    {
        $this->vars['users'] = [];

        try {
            $this->vars['users'] = $this->getData();
        }
        catch(\Exception $ex) {
            $this->vars['error'] = $ex->getMessage();
        }

        return $this->makePartial('widget');
    }

    /**
     * Получение данных для рендера графика регистраций
     * 
     * @return array
     */
    public function getData()
    {
        $days = $this->property('days');

        if (intval($days) <= 0) {
            throw new ApplicationException(Lang::get('keerill.users::lang.messages.invalid_days'));
        }

        $daysToRender = $this->createNullDays($days);
        $items = User::where('created_at', '>=', Carbon::now()->subDays($days)->format('Y-m-d'))->get();
        $sortedItems = $this->sortItemsToDays($items);

        $all = array_merge($daysToRender, $sortedItems);

        $renderData = [];
        foreach ($all as $item) {
            $renderData[] = [array_get($item, 'timestamp'), array_get($item, 'count')];
        }
        return $renderData;
    }

    /**
     * Сортировка предметов по дням
     *
     * @param $items
     *
     * @return array
     */
    private function sortItemsToDays($items)
    {
        $all = [];
        foreach ($items as $item)
        {

            $timestamp = strtotime($item->created_at) * 1000;
            $day = $item->created_at->format('Y-m-d');

            if (!isset($all[$day])) {
                $all[$day] = [
                    'timestamp' => $timestamp,
                    'count' => 0,
                ];
            }

            $all[$day]['count']++;
        }
        return $all;
    }

    /**
     * Создание пустых дней, для избежания искажения графика во времени
     * 
     * @param integer $days Количество дней для рендера
     * 
     * @return array
     */
    private function createNullDays($days)
    {
        if (intval($days) <= 0) {
            throw new ApplicationException(Lang::get('keerill.users::lang.messages.invalid_days'));
        }

        $all = [];
        $time = Carbon::now();

        for($i = 0; $i <= $days; $i++) {
            $all[$time->format('Y-m-d')] = [
                'timestamp' => strtotime($time) * 1000,
                'count' => 0
            ];

            $time->subDay(1);
        }

        return $all;
    }
}