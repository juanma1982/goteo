<?php
/*
 * This file is part of the Goteo Package.
 *
 * (c) Platoniq y Fundación Goteo <fundacion@goteo.org>
 *
 * For the full copyright and license information, please view the README.md
 * and LICENSE files that was distributed with this source code.
 */

namespace Goteo\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Goteo\Application\Exception\ControllerAccessDeniedException;
use Goteo\Application\Exception\ControllerException;
use Goteo\Library\Text;
use Goteo\Application\Currency;
use Goteo\Payment\Payment;
use Goteo\Core\Model;
use Goteo\Model\Project;
use Goteo\Model\Call;
use Goteo\Model\Invest;
use Goteo\Model\Image;
use Goteo\Model\Origin;
use Goteo\Util\Stats\Stats;


class ChartsApiController extends AbstractApiController {

    protected function getProject($prj, $private = false) {
        if( ! $prj instanceOf Project) {
            $prj = Project::get($prj);
        }

        $is_visible = in_array($prj->status, [Project::STATUS_IN_CAMPAIGN, Project::STATUS_FUNDED, Project::STATUS_FULFILLED, Project::STATUS_UNFUNDED]) && !$private;

        $is_mine = $prj->owner === $this->user->id;
        if(!$this->is_admin && !$is_mine && !$is_visible) {
            throw new ControllerAccessDeniedException();
        }
        return $prj;
    }

    protected function getCall($call, $private = false) {
        if( ! $call instanceOf Call) {
            $call = Call::get($call);
        }

        $is_visible = in_array($call->status, [Call::STATUS_OPEN, Call::STATUS_ACTIVE, Call::STATUS_COMPLETED]) && !$private;

        $is_mine = $call->owner === $this->user->id;
        if(!$this->is_admin && !$is_mine && !$is_visible) {
            throw new ControllerAccessDeniedException();
        }
        return $call;
    }

    /**
     * Simple projects info data specially formatted for D3 charts
     * COSTS
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function projectCostsAction($id, Request $request) {
        $prj = $this->getProject($id);
        $mincost = (int) Currency::amount($prj->mincost);
        $maxcost = (int) Currency::amount($prj->maxcost);

        //add costs
        $costs = ['mandatory' => [], 'optional' => []];

        //Light colors
        $light_o=30;
        $light_m=40;

        foreach(Project\Cost::getAll($id) as $cost) {
            $arr = $cost->required ? 'mandatory' : 'optional';

            if($arr=='mandatory')
            {
                $light_m+=4;
                $color='hsla(303, 46%,'. $light_m.'%, 1)';
                $color_first='hsla(303, 46%, 40%, 1)';
            }
            else
            {
                $light_o-=4;
                $color='hsla(179, 76%,'. $light_m.'%, 1)';
                $color_first='hsla(179, 76%, 30%, 1)';
            }

            if(!is_array($costs[$arr][$cost->type])) $costs[$arr][$cost->type] = ['name' => Text::get('cost-type-' . $cost->type), 'size' => 0, 'color' => $color, 'children' => []];
            $costs[$arr][$cost->type]['children'][] = ['name' => $cost->cost, 'title' => \amount_format($cost->amount), 'color' => $color, 'size' => (int)$cost->amount];
            $costs[$arr][$cost->type]['size'] += (int)$cost->amount;
            $costs[$arr][$cost->type]['color'] = $color_first;
        }
        // $ob['children'] = array_values($costs);
        $ob = ['size' => $mincost + $maxcost, 'name' => $prj->name, 'children' => [
            ['size' => $mincost, 'name' => Text::get('project-view-metter-minimum'), 'color' =>'hsla(303, 46%, 30%, 1)', 'children' => array_values($costs['mandatory'])],
            ['size' => $maxcost, 'name' => Text::get('project-view-metter-optimum'), 'color' =>'hsla(179, 76%, 20%, 1)', 'children' => array_values($costs['optional'])],
        ]];

        return $this->jsonResponse($ob);
    }

    /**
     * Simple projects info data specially formatted for D3 charts
     * INVESTS
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function projectInvestsAction($id, Request $request) {
        $prj = $this->getProject($id);
        $mincost = (int) Currency::amount($prj->mincost);
        $maxcost = (int) Currency::amount($prj->maxcost);
        // die("[ $mincost $maxcost ]");
        // print_r($prj);die;
        $empty = ['date' => null, 'amount' => 0, 'ideal' => 0, 'invests' => 0, 'desc' => 'Invest', 'cumulative' => 0, 'cumulative-invests' => 0, 'cumulative-amount-percent' => 0, 'cumulative-invests-percent' => 0];
        // print_r($prj);die;
        $time = strtotime($prj->published);
        $time_round1 = mktime(0,0,0,date('m', $time), date('d', $time) + (int)$prj->days_round1, date('Y', $time));
        $time_round2 = mktime(0,0,0,date('m', $time), date('d', $time) + (int)$prj->days_round1 + (int)$prj->days_round2, date('Y', $time));
        $round1 = date("Y-m-d", $time_round1);
        $round2 = date("Y-m-d", $time_round2);
        $ob = [
            // Initial point
            $prj->published => ['date' => $prj->published, 'desc' => 'Campaign start'] + $empty,
            // Ideal minimum point
            $round1 => ['date' => $round1, 'ideal' => $mincost, 'desc' => 'End Round 1'] + $empty
        ];

        // Ideal optimum point
        if(!$prj->one_round) {
            $ob[$round2] = ['date' => $round2, 'ideal' => $maxcost, 'desc' => 'End Round 2'] + $empty;
        }

        $cumulative = $cumulative_invests = 0;
        $total = $prj->getTotalInvestions();
        foreach($prj->getInvestions(0, $total) as $invest) {
            $amount = Currency::amount($invest->amount);
            if(is_array($ob[$invest->invested])) {
                $ob[$invest->invested]['amount'] += $amount;
                $ob[$invest->invested]['invests'] ++;
            } else {
                $t = strtotime($invest->invested);
                if($t <= $time_round1) {
                    $ideal = round($mincost * ($t - $time) / ($time_round1 - $time));
                }
                else {
                    $ideal = round(($maxcost - $mincost) * ($t - $time_round1) / ($time_round2 - $time_round1)) + $mincost;
                }
                $ob[$invest->invested] = ['date' => $invest->invested, 'amount' => $amount, 'ideal' => $ideal, 'invests' => 1] + $empty;
            }
            $cumulative += $amount;
            $cumulative_invests ++;
            $ob[$invest->invested]['cumulative'] = round($cumulative);
            $ob[$invest->invested]['cumulative-invests'] = round($cumulative_invests);
            $ob[$invest->invested]['cumulative-amount-percent'] = $mincost ? round(100 * $cumulative / $mincost, 2) : 0;
            $ob[$invest->invested]['cumulative-invests-percent'] = $total ? round(100 * $cumulative_invests / $total, 2) : 0;
        }
        ksort($ob);
        $ret = [];
        $last = 0;
        foreach($ob as $k => $v) {
            if(empty($v['invests'])) {
                $v['cumulative'] = $last['cumulative'];
                $v['cumulative-invests'] = $last['cumulative-invests'];
                $v['cumulative-amount-percent'] = $last['cumulative-amount-percent'];
                $v['cumulative-invests-percent'] = $last['cumulative-invests-percent'];
            }
            $ret[] = $v;
            $last = $v;
        }
        return $this->jsonResponse($ret);
    }


    /**
     * Simple projects origins data
     * @param  Request $request [description]
     */
    public function originStatsAction($id = null, $type = 'project', $group = 'referer', Request $request) {

        if($type === 'call')
            $model = $id ? $this->getCall($id, true) : new Call();
        else
            $model = $id ? $this->getProject($id, true) : new Project();

        $group_by = $request->query->get('group_by');
        $filters = [
            'from' =>$request->query->get('from'),
            'to' => $request->query->get('to')
        ];

        if($type === 'invests') $ret = Origin::getInvestsStats($model, $group, $group_by, $filters);
        else $ret = Origin::getModelStats($model, $group, $group_by, $filters);

        $ret = array_map(function($ob) use ($group_by) {
            $label = $ob->tag ? $ob->tag : 'unknown';
            if($group_by === 'category') $label = $ob->category ? $ob->category : 'unknown';
            elseif($ob->category === 'internal') {
                $label = $ob->category . ": " . ucfirst($label);
            }

            return [
                'label' => ucfirst($label),
                'counter' => (int) $ob->counter,
                'created' => $ob->created,
                'updated' => $ob->updated
            ];
        }, $ret);

        return $this->jsonResponse($ret);
    }

    /**
     * Groups data in time units (5min, 1 hour, 1 day, etc)
     * @param  Request $request [description]
     */
    public function aggregatesAction($type = 'invests', Request $request) {
        $limit = 100;

        if($type === 'invests') {
            $table = 'invest';
            $datetime = 'datetime';
            $amount = 'amount';
            $where = "WHERE invest.status IN (". implode(',', Invest::$ACTIVE_STATUSES) . ")";
        } elseif($type === 'projects') {
            $table = 'project';
            $datetime = 'published';
            $amount = 'amount';
            $where = "WHERE project.status > (" . PROJECT::STATUS_REVIEWING . ")";
        }
        $values = [];

        $ob = Model::query("SELECT
            COUNT(*) AS `total`,
            MIN(`$datetime`) AS min_date,
            MAX(`$datetime`) AS max_date
             FROM `$table` $where", $values)->fetchObject();
        $total_items = (int) $ob->total;
        $min_date = $ob->min_date;
        $max_date = $ob->max_date;

        if($from = $request->query->get('from')) {
            if(!\date_valid($from)) throw new ControllerException("Date 'from' [$from] is invalid");
            $where .= " AND `$datetime` >= :from";
            $values[':from'] = $from;
            $min_date = $from;
        }
        if($to = $request->query->get('to')) {
            if(!\date_valid($to)) throw new ControllerException("Date 'to' [$to] is invalid");
            $where .= " AND `$datetime` <= :to";
            $values[':to'] = $to;
            $max_date = $to;
        }

        $diff = (new \DateTime($min_date))->diff(new \DateTime($max_date));
        $diff_years = $diff->y;
        $diff_months = $diff_years*12 + $diff->m;
        $diff_hours = $diff->days*24 + $diff->h;
        $diff_minutes = $diff_hours*60 + $diff->i;
        $diff_seconds = $diff_minutes*60 + $diff->s;

        // print_r($diff);die;
        $granularity =  max(60, 60 * floor($diff_minutes / $limit)); // minimun granularity is 1 minute
        $div = "UNIX_TIMESTAMP(`$datetime`) DIV $granularity";

        $total = (int)Model::query("SELECT count(*) FROM (SELECT COUNT(*) FROM `$table` $where GROUP BY $div) AS sub", $values)->fetchColumn();

        $sql = "SELECT
            FROM_UNIXTIME(($div) * $granularity) AS `date`,
            SUM(`$amount`) AS `total`,
            AVG(`$amount`) AS `average`,
            COUNT(*) AS `count`
        FROM `$table`
        $where
        GROUP BY `date`
        ORDER BY `date` DESC
        LIMIT $limit";

        $items = [];
        if($query = Model::query($sql, $values)) {
            foreach($query->fetchAll(\PDO::FETCH_OBJ) as $ob) {
                $ob->total = \amount_format($ob->total, 0, true, false, false);
                $ob->count = (int) $ob->count;
                $ob->average = round($ob->average, 2);
                $items[] = $ob;
            }
        }

        return $this->jsonResponse([
            'granularity' => $granularity,
            'granularity_hours' => floor($granularity/3600),
            'granularity_days' => floor($granularity/(3600*24)),
            'limit' => $limit,
            'total' => $total,
            'total_items' => $total_items,
            'min_date' => $min_date,
            'max_date' => $max_date,
            'diff_seconds' => $diff_seconds,
            'diff_minutes' => $diff_minutes,
            'diff_hours' => $diff_hours,
            'diff_days' => $diff->days,
            'diff_months' => $diff_months,
            'diff_years' => $diff_years,
            'items' => $items
        ]);
    }


    private function timeSlots() {
        return [
            'today' => ['from' => (new \DateTime('today'))->format('Y-m-d'),
                        'to' => (new \DateTime('today'))->format('Y-m-d')],
            'yesterday' => ['from' => (new \DateTime('yesterday'))->format('Y-m-d'),
                            'to' => (new \DateTime('yesterday'))->format('Y-m-d')],
            'week' => ['from' => (new \DateTime('monday this week'))->format('Y-m-d'),
                       'to' => (new \DateTime('today'))->format('Y-m-d')],
            'month' => ['from' => (new \DateTime('first day of this month'))->format('Y-m-d'),
                        'to' => (new \DateTime('today'))->format('Y-m-d')],
            'year' => ['from' => (new \DateTime('first day of january this year'))->format('Y-m-d'),
                        'to' => (new \DateTime('today'))->format('Y-m-d')],
            'lastyear' => ['from' => (new \DateTime('first day of january last year'))->format('Y-m-d'),
                           'to' => (new \DateTime('last day of december last year'))->format('Y-m-d')],
        ];
    }

    /**
     * Gets totals for invests
     * @param  Request $request [description]
     */
    public function totalInvestsAction(Request $request) {
        // Use the Stats class to take advantage of the Caching component
        $stats = Stats::create('api_totals');
        $payments = [];
        $global = ['raised' => [], 'active' => [], 'commissions' => []];
        $fees = [];
        foreach(Payment::getMethods() as $method) {

            $raised = []; // payments including returned
            $active = []; // paid payments
            $commissions = []; // bank commissions

            foreach(self::timeSlots() as $slot => $dates) {
                $filter = ['status' => Invest::$RAISED_STATUSES,
                           'methods' => $method::getId(),
                           'date_from' => $dates['from'],
                           'date_until' => $dates['to']];

                $raised[$slot] = $stats->investsTotals($filter);
                $raised[$slot]['amount'] = \amount_format($raised[$slot]['amount']);

                $filter['status'] = Invest::$ACTIVE_STATUSES;
                $active[$slot] = $stats->investsTotals($filter);
                $active[$slot]['amount'] = \amount_format($active[$slot]['amount']);
            }

            // Bank Comissions
            foreach($raised as $slot => $part) {
                $returned_invests = $part['invests'] - $active[$slot]['invests'];
                $returned_amount = $part['amount'] - $active[$slot]['amount'];
                $commissions[$slot] = $method::calculateFee($part['invests'], $part['amount'], $returned_invests, $returned_amount);
                $global['commissions'][$slot] += $commissions[$slot];
                $commissions[$slot] = \amount_format($commissions[$slot]);
            }
            // print_r($commissions);die("$returned_invests $returned_amount ");

            $ret = [
                'raised' => $raised,
                'active' => $active,
                'commissions' => $commissions,
                'fees' => $fees
            ];
            $payments[$method::getId()] = $ret;

            // Skip pool method payment as is internal
            if(in_array($method::getId(), ['pool', 'dummy'])) continue;
            // Sum totals
            foreach($raised as $slot => $part) {
                foreach($part as $k => $v) {
                    $global['raised'][$slot][$k] += $v;
                }
            }
            foreach($active as $slot => $part) {
                foreach($part as $k => $v) {
                    $global['active'][$slot][$k] += $v;
                }
            }
        }

        foreach(self::timeSlots() as $slot => $dates) {
            // Goteo fee
            $pfilter = ['status' => [Project::STATUS_FUNDED, Project::STATUS_FULFILLED],
                        'success_from' => $dates['from'],
                        'success_until' => $dates['to']];
            $fees[$slot] = \amount_format($stats->projectsTotals($pfilter, 'fee'));
            $global['commissions'][$slot] = \amount_format($global['commissions'][$slot]);
        }

        return $this->jsonResponse([
            'payments' => $payments,
            'global' => $global,
            'fees' => $fees
        ]);
    }


        /**
     * Gets totals for invests
     * @param  Request $request [description]
     */
    public function totalProjectsAction(Request $request) {
        // Use the Stats class to take advantage of the Caching component
        $stats = Stats::create('api_totals');
        $projects= [];

        $ofilter = [];
        if($consultant = $request->query->get('consultant')) {
            $ofilter['consultant'] = $consultant;
        }
        foreach(['created', 'published', 'reviewing', 'rejected'] as $part) {
            $date_from = 'created_from';
            $date_until = 'created_until';
            $filter = $ofilter;
            if($part === 'published') {
                $filter['status'] = [Project::STATUS_IN_CAMPAIGN, Project::STATUS_FUNDED, Project::STATUS_FULFILLED, Project::STATUS_UNFUNDED];
                $date_from = 'published_from';
                $date_until = 'published_until';
            }
            elseif($part === 'reviewing') {
                $filter['status'] = Project::STATUS_REVIEWING;
                $date_from = 'updated_from';
                $date_until = 'updated_until';
            }
            elseif($part === 'rejected') {
                // Rejected project have updated date defined
                // $filter['status'] = [Project::STATUS_REJECTED, Project::STATUS_EDITING];
                $filter['status'] = Project::STATUS_REJECTED;
                $date_from = 'updated_from';
                $date_until = 'updated_until';
            }

            foreach(self::timeSlots() as $slot => $dates) {
                $filter[$date_from] = $dates['from'];
                $filter[$date_until] = $dates['to'];
                $projects[$part][$slot] = $stats->projectsTotals($filter, 'total');
                // $projects[$part][$slot] = rand(1,100);
            }

        }

        $pending = [];
        $filter = $ofilter;
        $filter['published_from'] = (new \DateTime('today'))->format('Y-m-d');
        $filter['status'] = Project::STATUS_REVIEWING;

        foreach(Project::getList($filter, null, 0, 100) as $prj) {
            $pending[] = [
                'id' => $prj->id,
                'image' => $prj->image ? $prj->image->getLink(64, 64, true) : '',
                'name' => $prj->name,
                'publish' => $prj->published,
                'created' => $prj->created,
                'status' => $prj->getTextStatus(),
                'consultants' => $prj->getConsultants(),
            ];
        }
        $projects['pending'] = $pending;
        return $this->jsonResponse($projects);
    }
}
