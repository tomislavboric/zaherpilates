const COUNTDOWN_SELECTOR = '.bf-countdown[data-deadline]';
const UNIT_KEYS = ['days', 'hours', 'minutes', 'seconds'];
const SECOND_IN_MS = 1000;
const MINUTE_IN_MS = SECOND_IN_MS * 60;
const HOUR_IN_MS = MINUTE_IN_MS * 60;
const DAY_IN_MS = HOUR_IN_MS * 24;

const formatValue = (value) => String(value).padStart(2, '0');

const ensureTextNode = (element) => {
	if (!element) {
		return null;
	}

	const existingTextNode = Array.from(element.childNodes).find(
		(node) => node.nodeType === Node.TEXT_NODE
	);

	if (existingTextNode) {
		return existingTextNode;
	}

	const textNode = document.createTextNode('00');
	element.insertBefore(textNode, element.firstChild || null);
	return textNode;
};

const initCountdown = (countdown) => {
	const deadlineAttr = countdown.getAttribute('data-deadline');
	const deadline = deadlineAttr ? new Date(deadlineAttr) : null;

	if (!deadline || Number.isNaN(deadline.getTime())) {
		return;
	}

	const unitNodes = UNIT_KEYS.reduce((acc, key) => {
		const element = countdown.querySelector(`[data-unit="${key}"]`);
		acc[key] = {
			element,
			textNode: ensureTextNode(element),
		};
		return acc;
	}, {});

	const update = () => {
		const now = Date.now();
		let diff = deadline.getTime() - now;

		if (diff <= 0) {
			diff = 0;
		}

		const days = Math.floor(diff / DAY_IN_MS);
		const hours = Math.floor((diff % DAY_IN_MS) / HOUR_IN_MS);
		const minutes = Math.floor((diff % HOUR_IN_MS) / MINUTE_IN_MS);
		const seconds = Math.floor((diff % MINUTE_IN_MS) / SECOND_IN_MS);

		if (unitNodes.days?.textNode) {
			unitNodes.days.textNode.nodeValue = formatValue(days);
		}
		if (unitNodes.hours?.textNode) {
			unitNodes.hours.textNode.nodeValue = formatValue(hours);
		}
		if (unitNodes.minutes?.textNode) {
			unitNodes.minutes.textNode.nodeValue = formatValue(minutes);
		}
		if (unitNodes.seconds?.textNode) {
			unitNodes.seconds.textNode.nodeValue = formatValue(seconds);
		}

		if (diff === 0) {
			countdown.classList.add('bf-countdown--finished');
			return true;
		}

		return false;
	};

	update();

	const intervalId = window.setInterval(() => {
		const finished = update();
		if (finished) {
			window.clearInterval(intervalId);
		}
	}, SECOND_IN_MS);
};

const mountCountdowns = () => {
	const countdowns = document.querySelectorAll(COUNTDOWN_SELECTOR);
	countdowns.forEach(initCountdown);
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', mountCountdowns);
} else {
	mountCountdowns();
}
