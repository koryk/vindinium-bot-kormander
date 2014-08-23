<?php

public class Logger {
	private static $logger = NULL;
	public function __construct() {
		openlog("orolobot.log", LOG_PID | LOG_PERROR, LOG_LOCAL0);
	}
	public static function log($text) {
		$logger->log($text);
	}
	public static function logger() {
		if (self::$logger == NULL) {
			self::$logger = new Logger();
		}
		return self::$logger;
	}
	public function log($text) {
		syslog(LOG_WARNING,$text);
	}
}
