const TRANSLATION_FUNCTIONS = [ '__', '_x', '_n', '_nx' ];

/**
 * Regular expression matching the presence of a printf format string
 * placeholder.
 *
 * Originally copied from http://php.net/manual/en/function.sprintf.php#93552.
 *
 * @see https://github.com/WordPress/WordPress-Coding-Standards/blob/2f927b0ba2bfcbffaa8f3251c086e109302d6622/WordPress/Sniffs/WP/I18nSniff.php#L37-L60
 *
 * @type {RegExp}
 */
const SPRINTF_PLACEHOLDER_REGEX = /(?:(?<!%)(%(?:[0-9]+\$)?[+-]?(?:(?:0|\'.)?-?[0-9]*(?:\.(?:[ 0]|\'.)?[0-9]+)?|(?:[ ])?-?[0-9]+(?:\.(?:[ 0]|\'.)?[0-9]+)?)[bcdeEfFgGosuxX]))/g;

/**
 * "Unordered" means there's no position specifier: '%s', not '%2$s'.
 *
 * @see https://github.com/WordPress/WordPress-Coding-Standards/blob/2f927b0ba2bfcbffaa8f3251c086e109302d6622/WordPress/Sniffs/WP/I18nSniff.php#L62-L81
 *
 * @type {RegExp}
 */
const UNORDERED_SPRINTF_PLACEHOLDER_REGEX = /(?:(?<!%)%[+-]?(?:(?:0|'.)?-?[0-9]*(?:\.(?:[ 0]|'.)?[0-9]+)?|(?:[ ])?-?[0-9]+(?:\.(?:[ 0]|'.)?[0-9]+)?)[bcdeEfFgGosuxX])/;

/**
 * Given a function name and array of argument Node values, returns all
 * possible string results from the corresponding translate function, or
 * undefined if the function is not a translate function.
 *
 * @param {string}        functionName Function name.
 * @param {espree.Node[]} args         Espree argument Node objects.
 *
 * @return {Array<string>|void} All possible translate function string results.
 */
function getTranslateStrings( functionName, args ) {
	switch ( functionName ) {
		case '__':
		case '_x':
			args = args.slice( 0, 1 );
			break;

		case '_n':
		case '_nx':
			args = args.slice( 0, 2 );
			break;

		default:
			return;
	}

	return args
		.filter( ( arg ) => arg.type === 'Literal' )
		.map( ( arg ) => arg.value );
}

module.exports = {
	TRANSLATION_FUNCTIONS,
	SPRINTF_PLACEHOLDER_REGEX,
	UNORDERED_SPRINTF_PLACEHOLDER_REGEX,
	getTranslateStrings,
};
