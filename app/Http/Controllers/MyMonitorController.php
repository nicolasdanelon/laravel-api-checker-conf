<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\UptimeMonitor\Models\Monitor;
use Spatie\Url\Url;

class MyMonitorController extends Controller
{
    use ValidatesRequests;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
	return $this->singleResultFormatter(Monitor::orderBy('created_at', 'asc')->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, config('laravel-uptime-monitor-api.validationRules'));
        $url = Url::fromString($request->get('url'));
        Monitor::create([
            'url' => trim($url, '/'),
            'look_for_string' => $request->get('look_for_string') ?? '',
            'uptime_check_method' => $request->has('look_for_string') ? 'get' : 'head',
            'certificate_check_enabled' => $url->getScheme() === 'https',
            'uptime_check_interval_in_minutes' => $request->get('uptime_check_interval_in_minutes'),
        ]);

        return response()->json(['created' => true]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // $server = Monitor::findOrFail($id);
        return $this->resultFormatter(Monitor::where('id', $id)->orderBy('id', 'asc')->get());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, config('laravel-uptime-monitor-api.validationRules'));

        $monitor = Monitor::findOrFail($id);
        $url = Url::fromString($request->get('url'));
        $look_for_string = ($request->has('look_for_string')) ? $request->get('look_for_string') : $monitor->look_for_string;
        $monitor->update([
            'url' => $request->get('url'),
            'look_for_string' => $look_for_string,
            'uptime_check_method' => $request->has('look_for_string') ? 'get' : 'head',
            'certificate_check_enabled' => $url->getScheme() === 'https',
            'uptime_check_interval_in_minutes' => $request->get('uptime_check_interval_in_minutes'),
        ]);

        return response()->json(['updated' => true]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $monitor = Monitor::findOrFail($id);
            $monitor->delete();
            $r = true;
        } catch (Exception $e) {
            $r = false;
        }
        return response()->json(['deleted' => $r]);
    }

    protected function singleResultFormatter($object)
    {
        $response = [];

        foreach ($object as $key => $server) {
            $url = $server->url->getScheme() . '://' . $server->url->getHost();

            if ($server->url->getPort()) {
                $url = $url . ':' . $server->url->getPort();
            }

            $response [] = [
                "id" => $server->id,
                "url" => $url,
                "uptime_status" => $server->uptime_status,
                "uptime_check_failure_reason" => $server->uptime_check_failure_reason,
                "updated_at" => is_null($server->updated_at) ?: $server->updated_at->format('Y-m-d H:i:s'),
            ];


        }

        return $response;
    }

    protected function resultFormatter($object)
    {
        $response = [];

        foreach ($object as $key => $server) {
            $url = $server->url->getScheme() . '://' . $server->url->getHost();

            if ($server->url->getPort()) {
                $url = $url . ':' . $server->url->getPort();
            }

            $response []= [
                "id" => $server->id,
                "url" => $url,
                "uptime_check_enabled" => $server->uptime_check_enabled,
                "look_for_string" => $server->look_for_string,
                "uptime_check_interval_in_minutes" => $server->uptime_check_interval_in_minutes,
                "uptime_status" => $server->uptime_status,
                "uptime_check_failure_reason" => $server->uptime_check_failure_reason,
                "uptime_check_times_failed_in_a_row" => $server->uptime_check_times_failed_in_a_row,
                "uptime_status_last_change_date" => is_null($server->uptime_status_last_change_date) ?: $server->uptime_status_last_change_date->format('Y-m-d H:i:s'),
                "uptime_last_check_date" => is_null($server->uptime_last_check_date) ?: $server->uptime_last_check_date->format('Y-m-d H:i:s'),
                "uptime_check_failed_event_fired_on_date" => is_null($server->uptime_check_failed_event_fired_on_date) ?: $server->uptime_check_failed_event_fired_on_date->format('Y-m-d H:i:s'),
                "uptime_check_method" => $server->uptime_check_method,
                "certificate_check_enabled" => $server->certificate_check_enabled,
                "certificate_status" => $server->certificate_status,
                "certificate_expiration_date" => is_null($server->certificate_expiration_date) ?: $server->certificate_expiration_date->format('Y-m-d H:i:s'),
                "certificate_issuer" => $server->certificate_issuer,
                "certificate_check_failure_reason" => $server->certificate_check_failure_reason,
                "created_at" => is_null($server->created_at) ?: $server->created_at->format('Y-m-d H:i:s'),
                "updated_at" => is_null($server->updated_at) ?: $server->updated_at->format('Y-m-d H:i:s'),
            ];
        }
        return $response;
    }
}
