/*! Parts:
 * 1. chartjs plugin for annotation (edited)
 * 2. main script for corona-lokaal.nl 
 */
"use strict";
/*!
 * chartjs-plugin-annotation.js
 * http://chartjs.org/
 * Version: 0.5.7
 *
 * Copyright 2016 Evert Timberg
 * Released under the MIT license
 * https://github.com/chartjs/Chart.Annotation.js/blob/master/LICENSE.md
 */
(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){

},{}],2:[function(require,module,exports){
module.exports = function(Chart) {
	var chartHelpers = Chart.helpers;

	var helpers = require('./helpers.js')(Chart);
	var events = require('./events.js')(Chart);

	var annotationTypes = Chart.Annotation.types;

	function setAfterDataLimitsHook(axisOptions) {
		helpers.decorate(axisOptions, 'afterDataLimits', function(previous, scale) {
			if (previous) previous(scale);
			helpers.adjustScaleRange(scale);
		});
	}

	function draw(drawTime) {
		return function(chartInstance, easingDecimal) {
			var defaultDrawTime = chartInstance.annotation.options.drawTime;

			helpers.elements(chartInstance)
				.filter(function(element) {
					return drawTime === (element.options.drawTime || defaultDrawTime);
				})
				.forEach(function(element) {
					element.transition(easingDecimal).draw();
				});
		};
	}

	return {
		beforeInit: function(chartInstance) {
			var chartOptions = chartInstance.options;

			// Initialize chart instance plugin namespace
			var ns = chartInstance.annotation = {
				elements: {},
				options: helpers.initConfig(chartOptions.annotation || {}),
				onDestroy: [],
				firstRun: true,
				supported: false
			};

			// Add the annotation scale adjuster to each scale's afterDataLimits hook
			chartInstance.ensureScalesHaveIDs();
			if (chartOptions.scales) {
				ns.supported = true;
				chartHelpers.each(chartOptions.scales.xAxes, setAfterDataLimitsHook);
				chartHelpers.each(chartOptions.scales.yAxes, setAfterDataLimitsHook);
			}
		},
		beforeUpdate: function(chartInstance) {
			var ns = chartInstance.annotation;

			if (!ns.supported) {
				return;
			}

			if (!ns.firstRun) {
				ns.options = helpers.initConfig(chartInstance.options.annotation || {});
			} else {
				ns.firstRun = false;
			}

			var elementIds = [];

			// Add new elements, or update existing ones
			ns.options.annotations.forEach(function(annotation) {
				var id = annotation.id || helpers.objectId();
				
				// No element with that ID exists, and it's a valid annotation type
				if (!ns.elements[id] && annotationTypes[annotation.type]) {
					var cls = annotationTypes[annotation.type];
					var element = new cls({
						id: id,
						options: annotation,
						chartInstance: chartInstance,
					});
					element.initialize();
					ns.elements[id] = element;
					annotation.id = id;
					elementIds.push(id);
				} else if (ns.elements[id]) {
					// Nothing to do for update, since the element config references
					// the same object that exists in the chart annotation config
					elementIds.push(id);
				}
			});

			// Delete removed elements
			Object.keys(ns.elements).forEach(function(id) {
				if (elementIds.indexOf(id) === -1) {
					ns.elements[id].destroy();
					delete ns.elements[id];
				}
			});
		},
		afterScaleUpdate: function(chartInstance) {
			helpers.elements(chartInstance).forEach(function(element) {
				element.configure();
			});
		},
		beforeDatasetsDraw: draw('beforeDatasetsDraw'),
		afterDatasetsDraw: draw('afterDatasetsDraw'),
		afterDraw: draw('afterDraw'),
		afterInit: function(chartInstance) {
			// Detect and intercept events that happen on an annotation element
			var watchFor = chartInstance.annotation.options.events;
			if (chartHelpers.isArray(watchFor) && watchFor.length > 0) {
				var canvas = chartInstance.chart.canvas;
				var eventHandler = events.dispatcher.bind(chartInstance);
				events.collapseHoverEvents(watchFor).forEach(function(eventName) {
					chartHelpers.addEvent(canvas, eventName, eventHandler);
					chartInstance.annotation.onDestroy.push(function() {
						chartHelpers.removeEvent(canvas, eventName, eventHandler);
					});
				});
			}
		},
		destroy: function(chartInstance) {
			var deregisterers = chartInstance.annotation.onDestroy;
			while (deregisterers.length > 0) {
				deregisterers.pop()();
			}
		}
	};
};

},{"./events.js":4,"./helpers.js":5}],3:[function(require,module,exports){
module.exports = function(Chart) {
	var chartHelpers = Chart.helpers;
	
	var AnnotationElement = Chart.Element.extend({
		initialize: function() {
			this.hidden = false;
			this.hovering = false;
			this._model = chartHelpers.clone(this._model) || {};
			this.setDataLimits();
		},
		destroy: function() {},
		setDataLimits: function() {},
		configure: function() {},
		inRange: function() {},
		getCenterPoint: function() {},
		getWidth: function() {},
		getHeight: function() {},
		getArea: function() {},
		draw: function() {}
	});

	return AnnotationElement;
};

},{}],4:[function(require,module,exports){
module.exports = function(Chart) {
	var chartHelpers = Chart.helpers;
	var helpers = require('./helpers.js')(Chart);

	function collapseHoverEvents(events) {
		var hover = false;
		var filteredEvents = events.filter(function(eventName) {
			switch (eventName) {
				case 'mouseenter':
				case 'mouseover':
				case 'mouseout':
				case 'mouseleave':
					hover = true;
					return false;

				default:
					return true;
			}
		});
		if (hover && filteredEvents.indexOf('mousemove') === -1) {
			filteredEvents.push('mousemove');
		}
		return filteredEvents;
	}

	function dispatcher(e) {
		var ns = this.annotation;
		var elements = helpers.elements(this);
		var position = chartHelpers.getRelativePosition(e, this.chart);
		var element = helpers.getNearestItems(elements, position);
		var events = collapseHoverEvents(ns.options.events);
		var dblClickSpeed = ns.options.dblClickSpeed;
		var eventHandlers = [];
		var eventHandlerName = helpers.getEventHandlerName(e.type);
		var options = (element || {}).options;

		// Detect hover events
		if (e.type === 'mousemove') {
			if (element && !element.hovering) {
				// hover started
				['mouseenter', 'mouseover'].forEach(function(eventName) {
					var eventHandlerName = helpers.getEventHandlerName(eventName);
					var hoverEvent = helpers.createMouseEvent(eventName, e); // recreate the event to match the handler
					element.hovering = true;
					if (typeof options[eventHandlerName] === 'function') {
						eventHandlers.push([ options[eventHandlerName], hoverEvent, element ]);
					}
				});
			} else if (!element) {
				// hover ended
				elements.forEach(function(element) {
					if (element.hovering) {
						element.hovering = false;
						var options = element.options;
						['mouseout', 'mouseleave'].forEach(function(eventName) {
							var eventHandlerName = helpers.getEventHandlerName(eventName);
							var hoverEvent = helpers.createMouseEvent(eventName, e); // recreate the event to match the handler
							if (typeof options[eventHandlerName] === 'function') {
								eventHandlers.push([ options[eventHandlerName], hoverEvent, element ]);
							}
						});
					}
				});
			}
		}

		// Suppress duplicate click events during a double click
		// 1. click -> 2. click -> 3. dblclick
		//
		// 1: wait dblClickSpeed ms, then fire click
		// 2: cancel (1) if it is waiting then wait dblClickSpeed ms then fire click, else fire click immediately
		// 3: cancel (1) or (2) if waiting, then fire dblclick 
		if (element && events.indexOf('dblclick') > -1 && typeof options.onDblclick === 'function') {
			if (e.type === 'click' && typeof options.onClick === 'function') {
				clearTimeout(element.clickTimeout);
				element.clickTimeout = setTimeout(function() {
					delete element.clickTimeout;
					options.onClick.call(element, e);
				}, dblClickSpeed);
				e.stopImmediatePropagation();
				e.preventDefault();
				return;
			} else if (e.type === 'dblclick' && element.clickTimeout) {
				clearTimeout(element.clickTimeout);
				delete element.clickTimeout;
			}
		}

		// Dispatch the event to the usual handler, but only if we haven't substituted it
		if (element && typeof options[eventHandlerName] === 'function' && eventHandlers.length === 0) {
			eventHandlers.push([ options[eventHandlerName], e, element ]);
		}

		if (eventHandlers.length > 0) {
			e.stopImmediatePropagation();
			e.preventDefault();
			eventHandlers.forEach(function(eventHandler) {
				// [handler, event, element]
				eventHandler[0].call(eventHandler[2], eventHandler[1]);
			});
		}
	}

	return {
		dispatcher: dispatcher,
		collapseHoverEvents: collapseHoverEvents
	};
};

},{"./helpers.js":5}],5:[function(require,module,exports){
function noop() {}

function elements(chartInstance) {
	// Turn the elements object into an array of elements
    var elements = chartInstance.annotation.elements;
	return Object.keys(elements).map(function(id) {
		return elements[id];
	});
}

function objectId() {
	return Math.random().toString(36).substr(2, 6);
}

function isValid(rawValue) {
	if (rawValue === null || typeof rawValue === 'undefined') {
		return false;
	} else if (typeof rawValue === 'number') {
		return isFinite(rawValue);
	} else {
		return !!rawValue;
	}
}

function decorate(obj, prop, func) {
	var prefix = '$';
	if (!obj[prefix + prop]) {
		if (obj[prop]) {
			obj[prefix + prop] = obj[prop].bind(obj);
			obj[prop] = function() {
				var args = [ obj[prefix + prop] ].concat(Array.prototype.slice.call(arguments));
				return func.apply(obj, args);
			};
		} else {
			obj[prop] = function() {
				var args = [ undefined ].concat(Array.prototype.slice.call(arguments));
				return func.apply(obj, args);
			};
		}
	}
}

function callEach(fns, method) {
	fns.forEach(function(fn) {
		(method ? fn[method] : fn)();
	});
}

function getEventHandlerName(eventName) {
	return 'on' + eventName[0].toUpperCase() + eventName.substring(1);
}

function createMouseEvent(type, previousEvent) {
	try {
		return new MouseEvent(type, previousEvent);
	} catch (exception) {
		try {
			var m = document.createEvent('MouseEvent');
			m.initMouseEvent(
				type,
				previousEvent.canBubble,
				previousEvent.cancelable,
				previousEvent.view,
				previousEvent.detail,
				previousEvent.screenX,
				previousEvent.screenY,
				previousEvent.clientX,
				previousEvent.clientY,
				previousEvent.ctrlKey,
				previousEvent.altKey,
				previousEvent.shiftKey,
				previousEvent.metaKey,
				previousEvent.button,
				previousEvent.relatedTarget
			);
			return m;
		} catch (exception2) {
			var e = document.createEvent('Event');
			e.initEvent(
				type,
				previousEvent.canBubble,
				previousEvent.cancelable
			);
			return e;
		}
	}
}

module.exports = function(Chart) {
	var chartHelpers = Chart.helpers;

	function initConfig(config) {
		config = chartHelpers.configMerge(Chart.Annotation.defaults, config);
		if (chartHelpers.isArray(config.annotations)) {
			config.annotations.forEach(function(annotation) {
				annotation.label = chartHelpers.configMerge(Chart.Annotation.labelDefaults, annotation.label);
			});
		}
		return config;
	}

	function getScaleLimits(scaleId, annotations, scaleMin, scaleMax) {
		var ranges = annotations.filter(function(annotation) {
			return !!annotation._model.ranges[scaleId];
		}).map(function(annotation) {
			return annotation._model.ranges[scaleId];
		});

		var min = ranges.map(function(range) {
			return Number(range.min);
		}).reduce(function(a, b) {
			return isFinite(b) && !isNaN(b) && b < a ? b : a;
		}, scaleMin);

		var max = ranges.map(function(range) {
			return Number(range.max);
		}).reduce(function(a, b) {
			return isFinite(b) && !isNaN(b) && b > a ? b : a;
		}, scaleMax);

		return {
			min: min,
			max: max
		};
	}

	function adjustScaleRange(scale) {
		// Adjust the scale range to include annotation values
		var range = getScaleLimits(scale.id, elements(scale.chart), scale.min, scale.max);
		if (typeof scale.options.ticks.min === 'undefined' && typeof scale.options.ticks.suggestedMin === 'undefined') {
			scale.min = range.min;
		}
		if (typeof scale.options.ticks.max === 'undefined' && typeof scale.options.ticks.suggestedMax === 'undefined') {
			scale.max = range.max;
		}
		if (scale.handleTickRangeOptions) {
			scale.handleTickRangeOptions();
		}
	}

	function getNearestItems(annotations, position) {
		var minDistance = Number.POSITIVE_INFINITY;

		return annotations
			.filter(function(element) {
				return element.inRange(position.x, position.y);
			})
			.reduce(function(nearestItems, element) {
				var center = element.getCenterPoint();
				var distance = chartHelpers.distanceBetweenPoints(position, center);

				if (distance < minDistance) {
					nearestItems = [element];
					minDistance = distance;
				} else if (distance === minDistance) {
					// Can have multiple items at the same distance in which case we sort by size
					nearestItems.push(element);
				}

				return nearestItems;
			}, [])
			.sort(function(a, b) {
				// If there are multiple elements equally close,
				// sort them by size, then by index
				var sizeA = a.getArea(), sizeB = b.getArea();
				return (sizeA > sizeB || sizeA < sizeB) ? sizeA - sizeB : a._index - b._index;
			})
			.slice(0, 1)[0]; // return only the top item
	}

	return {
		initConfig: initConfig,
		elements: elements,
		callEach: callEach,
		noop: noop,
		objectId: objectId,
		isValid: isValid,
		decorate: decorate,
		adjustScaleRange: adjustScaleRange,
		getNearestItems: getNearestItems,
		getEventHandlerName: getEventHandlerName,
		createMouseEvent: createMouseEvent
	};
};


},{}],6:[function(require,module,exports){
// Get the chart variable
var Chart = require('chart.js');
Chart = typeof(Chart) === 'function' ? Chart : window.Chart;

// Configure plugin namespace
Chart.Annotation = Chart.Annotation || {};

Chart.Annotation.drawTimeOptions = {
	afterDraw: 'afterDraw',
	afterDatasetsDraw: 'afterDatasetsDraw',
	beforeDatasetsDraw: 'beforeDatasetsDraw'
};

Chart.Annotation.defaults = {
	drawTime: 'afterDatasetsDraw',
	dblClickSpeed: 350, // ms
	events: [],
	annotations: []
};

Chart.Annotation.labelDefaults = {
	backgroundColor: 'rgba(0,0,0,0.8)',
	fontFamily: Chart.defaults.global.defaultFontFamily,
	fontSize: Chart.defaults.global.defaultFontSize,
	fontStyle: 'bold',
	fontColor: '#fff',
	xPadding: 6,
	yPadding: 6,
	cornerRadius: 6,
	position: 'center',
	xAdjust: 0,
	yAdjust: 0,
	enabled: false,
	content: null
};

Chart.Annotation.Element = require('./element.js')(Chart);

Chart.Annotation.types = {
	line: require('./types/line.js')(Chart),
	box: require('./types/box.js')(Chart)
};

var annotationPlugin = require('./annotation.js')(Chart);

module.exports = annotationPlugin;
Chart.pluginService.register(annotationPlugin);

},{"./annotation.js":2,"./element.js":3,"./types/box.js":7,"./types/line.js":8,"chart.js":1}],7:[function(require,module,exports){
// Box Annotation implementation
module.exports = function(Chart) {
	var helpers = require('../helpers.js')(Chart);
	
	var BoxAnnotation = Chart.Annotation.Element.extend({
		setDataLimits: function() {
			var model = this._model;
			var options = this.options;
			var chartInstance = this.chartInstance;

			var xScale = chartInstance.scales[options.xScaleID];
			var yScale = chartInstance.scales[options.yScaleID];
			var chartArea = chartInstance.chartArea;

			// Set the data range for this annotation
			model.ranges = {};
			
			if (!chartArea) {
				return;
			}
			
			var min = 0;
			var max = 0;
			
			if (xScale) {
				min = helpers.isValid(options.xMin) ? options.xMin : xScale.getPixelForValue(chartArea.left);
				max = helpers.isValid(options.xMax) ? options.xMax : xScale.getPixelForValue(chartArea.right);

				model.ranges[options.xScaleID] = {
					min: Math.min(min, max),
					max: Math.max(min, max)
				};
			}

			if (yScale) {
				min = helpers.isValid(options.yMin) ? options.yMin : yScale.getPixelForValue(chartArea.bottom);
				max = helpers.isValid(options.yMax) ? options.yMax : yScale.getPixelForValue(chartArea.top);

				model.ranges[options.yScaleID] = {
					min: Math.min(min, max),
					max: Math.max(min, max)
				};
			}
		},
		configure: function() {
			var model = this._model;
			var options = this.options;
			var chartInstance = this.chartInstance;

			var xScale = chartInstance.scales[options.xScaleID];
			var yScale = chartInstance.scales[options.yScaleID];
			var chartArea = chartInstance.chartArea;

			// clip annotations to the chart area
			model.clip = {
				x1: chartArea.left,
				x2: chartArea.right,
				y1: chartArea.top,
				y2: chartArea.bottom
			};

			var left = chartArea.left, 
				top = chartArea.top, 
				right = chartArea.right, 
				bottom = chartArea.bottom;

			var min, max;

			if (xScale) {
				min = helpers.isValid(options.xMin) ? xScale.getPixelForValue(options.xMin) : chartArea.left;
				max = helpers.isValid(options.xMax) ? xScale.getPixelForValue(options.xMax) : chartArea.right;
				left = Math.min(min, max);
				right = Math.max(min, max);
			}

			if (yScale) {
				min = helpers.isValid(options.yMin) ? yScale.getPixelForValue(options.yMin) : chartArea.bottom;
				max = helpers.isValid(options.yMax) ? yScale.getPixelForValue(options.yMax) : chartArea.top;
				top = Math.min(min, max);
				bottom = Math.max(min, max);
			}

			// Ensure model has rect coordinates
			model.left = left;
			model.top = top;
			model.right = right;
			model.bottom = bottom;

			// Stylistic options
			model.borderColor = options.borderColor;
			model.borderWidth = options.borderWidth;
			model.backgroundColor = options.backgroundColor;
		},
		inRange: function(mouseX, mouseY) {
			var model = this._model;
			return model &&
				mouseX >= model.left && 
				mouseX <= model.right && 
				mouseY >= model.top && 
				mouseY <= model.bottom;
		},
		getCenterPoint: function() {
			var model = this._model;
			return {
				x: (model.right + model.left) / 2,
				y: (model.bottom + model.top) / 2
			};
		},
		getWidth: function() {
			var model = this._model;
			return Math.abs(model.right - model.left);
		},
		getHeight: function() {
			var model = this._model;
			return Math.abs(model.bottom - model.top);
		},
		getArea: function() {
			return this.getWidth() * this.getHeight();
		},
		draw: function() {
			var view = this._view;
			var ctx = this.chartInstance.chart.ctx;

			ctx.save();

			// Canvas setup
			ctx.beginPath();
			ctx.rect(view.clip.x1, view.clip.y1, view.clip.x2 - view.clip.x1, view.clip.y2 - view.clip.y1);
			ctx.clip();

			ctx.lineWidth = view.borderWidth;
			ctx.strokeStyle = view.borderColor;
			ctx.fillStyle = view.backgroundColor;

			// Draw
			var width = view.right - view.left,
				height = view.bottom - view.top;
			ctx.fillRect(view.left, view.top, width, height);
			ctx.strokeRect(view.left, view.top, width, height);

			ctx.restore();
		}
	});

	return BoxAnnotation;
};

},{"../helpers.js":5}],8:[function(require,module,exports){
// Line Annotation implementation
module.exports = function(Chart) {
	var chartHelpers = Chart.helpers;
	var helpers = require('../helpers.js')(Chart);

	var horizontalKeyword = 'horizontal';
	var verticalKeyword = 'vertical';

	var LineAnnotation = Chart.Annotation.Element.extend({
		setDataLimits: function() {
			var model = this._model;
            var options = this.options;

			// Set the data range for this annotation
			model.ranges = {};
			model.ranges[options.scaleID] = {
				min: options.value,
                max: options.endValue || options.value
                /** Edit by Kalahiri 2021 */
                , index: options.index
                /** End of edit by Kalahiri 2021 */
            };
		},
		configure: function() {
			var model = this._model;
			var options = this.options;
			var chartInstance = this.chartInstance;
			var ctx = chartInstance.chart.ctx;

			var scale = chartInstance.scales[options.scaleID];
			var pixel, endPixel;
			if (scale) {
                
                /** Edit by Kalahiri 2021: Add the option to give the index of the array instead of the value.*/
                //pixel = helpers.isValid(options.value) ? scale.getPixelForValue(options.value) : NaN;
                pixel = helpers.isValid(options.value) ? scale.getPixelForValue(options.value,options.index) : NaN;
                // we want the annotation not on the tick but exactly middle between ticks
                if( options.beforeTick ) {
                    pixel -= ( scale.getPixelTickWidth() / 2 );
                }
                /** End of edit by Kalahiri 2021 */

				endPixel = helpers.isValid(options.endValue) ? scale.getPixelForValue(options.endValue) : pixel;
            }
			if (isNaN(pixel)) {
				return;
			}

			var chartArea = chartInstance.chartArea;

			// clip annotations to the chart area
			model.clip = {
				x1: chartArea.left,
				x2: chartArea.right,
				y1: chartArea.top,
				y2: chartArea.bottom
            };
            
            /** Edit by Kalahiri 2021: lower the height of the annotation by 'heightAdjust' pixels*/
            options.heightAdjust = helpers.isValid( options.heightAdjust ) ? options.heightAdjust : 0;
            /** End of edit by Kalahiri 2021 */

			if (this.options.mode == horizontalKeyword) {
				model.x1 = chartArea.left;
				model.x2 = chartArea.right;
				model.y1 = pixel;
                model.y2 = endPixel;
			} else {
                /** Edit by Kalahiri 2021: lower the height of the annotation by 'heightAdjust' pixels*/
				// model.y1 = chartArea.top;
                model.y1 = chartArea.top + options.heightAdjust;
                /** End of edit by Kalahiri 2021 */
				model.y2 = chartArea.bottom;
				model.x1 = pixel;
                model.x2 = endPixel;
			}

			model.line = new LineFunction(model);
            model.mode = options.mode;


			// Figure out the label:
			model.labelBackgroundColor = options.label.backgroundColor;
			model.labelFontFamily = options.label.fontFamily;
			model.labelFontSize = options.label.fontSize;
			model.labelFontStyle = options.label.fontStyle;
			model.labelFontColor = options.label.fontColor;
			model.labelXPadding = options.label.xPadding;
			model.labelYPadding = options.label.yPadding;
			model.labelCornerRadius = options.label.cornerRadius;
			model.labelPosition = options.label.position;
			model.labelXAdjust = options.label.xAdjust;
			model.labelYAdjust = options.label.yAdjust;
			model.labelEnabled = options.label.enabled;
			model.labelContent = options.label.content;

			ctx.font = chartHelpers.fontString(model.labelFontSize, model.labelFontStyle, model.labelFontFamily);
			var textWidth = ctx.measureText(model.labelContent).width;
			var textHeight = ctx.measureText('M').width;
			var labelPosition = calculateLabelPosition(model, textWidth, textHeight, model.labelXPadding, model.labelYPadding);
			model.labelX = labelPosition.x - model.labelXPadding;
			model.labelY = labelPosition.y - model.labelYPadding;
			model.labelWidth = textWidth + (2 * model.labelXPadding);
			model.labelHeight = textHeight + (2 * model.labelYPadding);

			model.borderColor = options.borderColor;
			model.borderWidth = options.borderWidth;
			model.borderDash = options.borderDash || [];
            model.borderDashOffset = options.borderDashOffset || 0;
		},
		inRange: function(mouseX, mouseY) {
			var model = this._model;
			
			return (
				// On the line
				model.line &&
				model.line.intersects(mouseX, mouseY, this.getHeight())
			) || (
				// On the label
				model.labelEnabled &&
				model.labelContent &&
				mouseX >= model.labelX && 
				mouseX <= model.labelX + model.labelWidth && 
				mouseY >= model.labelY && 
				mouseY <= model.labelY + model.labelHeight
			);
		},
		getCenterPoint: function() {
			return {
				x: (this._model.x2 + this._model.x1) / 2,
				y: (this._model.y2 + this._model.y1) / 2
			};
		},
		getWidth: function() {
			return Math.abs(this._model.right - this._model.left);
		},
		getHeight: function() {
			return this._model.borderWidth || 1;
		},
		getArea: function() {
			return Math.sqrt(Math.pow(this.getWidth(), 2) + Math.pow(this.getHeight(), 2));
		},
		draw: function() {
			var view = this._view;
			var ctx = this.chartInstance.chart.ctx;

			if (!view.clip) {
				return;
			}

			ctx.save();

			// Canvas setup
			ctx.beginPath();
			ctx.rect(view.clip.x1, view.clip.y1, view.clip.x2 - view.clip.x1, view.clip.y2 - view.clip.y1);
			ctx.clip();

			ctx.lineWidth = view.borderWidth;
			ctx.strokeStyle = view.borderColor;

			if (ctx.setLineDash) {
				ctx.setLineDash(view.borderDash);
			}
			ctx.lineDashOffset = view.borderDashOffset;

			// Draw
			ctx.beginPath();
			ctx.moveTo(view.x1, view.y1);
			ctx.lineTo(view.x2, view.y2);
			ctx.stroke();

			if (view.labelEnabled && view.labelContent) {
				ctx.beginPath();
				ctx.rect(view.clip.x1, view.clip.y1, view.clip.x2 - view.clip.x1, view.clip.y2 - view.clip.y1);
				ctx.clip();

				ctx.fillStyle = view.labelBackgroundColor;
				// Draw the tooltip
				chartHelpers.drawRoundedRectangle(
					ctx,
					view.labelX, // x
					view.labelY, // y
					view.labelWidth, // width
					view.labelHeight, // height
					view.labelCornerRadius // radius
				);
				ctx.fill();

				// Draw the text
				ctx.font = chartHelpers.fontString(
					view.labelFontSize,
					view.labelFontStyle,
					view.labelFontFamily
				);
				ctx.fillStyle = view.labelFontColor;
				ctx.textAlign = 'center';
				ctx.textBaseline = 'middle';
				ctx.fillText(
					view.labelContent,
					view.labelX + (view.labelWidth / 2),
					view.labelY + (view.labelHeight / 2)
				);
			}

			ctx.restore();
		}
	});

	function LineFunction(view) {
		// Describe the line in slope-intercept form (y = mx + b).
		// Note that the axes are rotated 90° CCW, which causes the
		// x- and y-axes to be swapped.
		var m = (view.x2 - view.x1) / (view.y2 - view.y1);
		var b = view.x1 || 0;

		this.m = m;
		this.b = b;

		this.getX = function(y) {
			// Coordinates are relative to the origin of the canvas
			return m * (y - view.y1) + b;
		};

		this.getY = function(x) {
			return ((x - b) / m) + view.y1;
		};

		this.intersects = function(x, y, epsilon) {
			epsilon = epsilon || 0.001;
			var dy = this.getY(x),
				dx = this.getX(y);
			return (
				(!isFinite(dy) || Math.abs(y - dy) < epsilon) &&
				(!isFinite(dx) || Math.abs(x - dx) < epsilon)
			);
		};
	}

	function calculateLabelPosition(view, width, height, padWidth, padHeight) {
		var line = view.line;
		var ret = {}, xa = 0, ya = 0;

		switch (true) {
			// top align
			case view.mode == verticalKeyword && view.labelPosition == "top":
				ya = padHeight + view.labelYAdjust;
				xa = (width / 2) + view.labelXAdjust;
				ret.y = view.y1 + ya;
				ret.x = (isFinite(line.m) ? line.getX(ret.y) : view.x1) - xa;
			break;

			// bottom align
			case view.mode == verticalKeyword && view.labelPosition == "bottom":
				ya = height + padHeight + view.labelYAdjust;
				xa = (width / 2) + view.labelXAdjust;
				ret.y = view.y2 - ya;
				ret.x = (isFinite(line.m) ? line.getX(ret.y) : view.x1) - xa;
			break;

			// left align
			case view.mode == horizontalKeyword && view.labelPosition == "left":
				xa = padWidth + view.labelXAdjust;
				ya = -(height / 2) + view.labelYAdjust;
				ret.x = view.x1 + xa;
				ret.y = line.getY(ret.x) + ya;
			break;

			// right align
			case view.mode == horizontalKeyword && view.labelPosition == "right":
				xa = width + padWidth + view.labelXAdjust;
				ya = -(height / 2) + view.labelYAdjust;
				ret.x = view.x2 - xa;
				ret.y = line.getY(ret.x) + ya;
			break;

			// center align
			default:
				ret.x = ((view.x1 + view.x2 - width) / 2) + view.labelXAdjust;
				ret.y = ((view.y1 + view.y2 - height) / 2) + view.labelYAdjust;
		}

		return ret;
	}

	return LineAnnotation;
};

},{"../helpers.js":5}]},{},[6]);
/*!
* Script for corona-lokaal.nl
* https://www.corona-lokaal.nl/assets/script/script.2.5.3.js
* Version: 2.5.3
*
* Copyright 2020-2025 Kalahiri
* Released and may be used under the GPLv3 license
* https://www.gnu.org/licenses/gpl-3.0.html
 */


// Namespaces
window.CL = window.CL || {};
window.CL.interactions = window.CL.interactions || {};  // user interactions: mouse wheel, touch, etc.
window.CL.display = window.CL.display || {};
window.CL.dataUtils = window.CL.dataUtils || {};
window.CL.maps = window.CL.maps || {};
window.CL.charts = window.CL.charts || {};
window.CL.texts = window.CL.texts || {};
window.CL.main = window.CL.main || {};









( function ( interactions, CL ) {


    // some settings for navigating the map using touchscreens
    /*
    CL.touchscreen = false;
    window.clicked = false;
    window.clickTimer = 0;
    */


/**
 * Download Chart as an Image
 */

 interactions.saveCanvasAsImage = function( imageName, canvasElement ) {
    const imageDataURL = canvasElement.toDataURL('image/png');
    const datestring =  new Date().toISOString();
    const datetimestring =  'D' + datestring.substring(0,datestring.length-5).replaceAll( '-','.').replaceAll( ':','.').replaceAll( 'T','.T');

    const downloadImage = document.createElement("a");
    document.body.appendChild(downloadImage);
    downloadImage.setAttribute( "download", imageName + "." + datetimestring + ".corona-lokaal.nl.png" );
    downloadImage.href = imageDataURL;
    downloadImage.click();
    downloadImage.remove();

};











}( window.CL.interactions = window.CL.interactions || {}, window.CL ) );




/**
 * User Experience Object: - animations
 * Namespace: display
 */
( function ( display ) {

    /**
     * Scroll smoothly to top of the page
     */
    display.scrollToTop = function( gototop = true ) {
        if( 0 === window.scrollY ) return;  // Already at the top.
        let scrollToPosition;
        if( gototop ) {
            scrollToPosition = 0;
        } else {
            var rect = document.getElementById('container-waste-water').getBoundingClientRect();
            scrollToPosition = window.scrollY + rect.top - 10;
        }
        window.scrollTo({
            top: scrollToPosition,
            behavior: "smooth"
        });
    };




 /**
     * Slide Down or Slide Up an element in Vanilla JS without using jQuery
     * Notice: The animation sliding up or down doesn't work on borders, so do not use thick borders for a nice animation
     * 
     * @param { element } element to show or to hide
     * @param { string } toggle can be 'show', 'hide' or 'toggle'.
     * @param { int } speed is the animation speed sliding up or down in milliseconds
     * @param { int } maxHeight The maxheight of the element if shown. maxHeight is necessary for the animation to work
     *                          Don't set it too much above expected height for a nice transition.
     * @param { element } referenceElement van be used to get the maxHeight from, if a similar element is available.
     * @return { bool } current status of the element. if visible return true, else return false. 
     */

  display.slideUpDown = function( element, toggle = 'show', speed = 4000, maxHeight = 300, referenceElement = null ) {

    if ( null != referenceElement ) {
        maxHeight = referenceElement.offsetHeight + 50; // some margin to be safe. It nibbles only a bit of the animation.
    }

    if( 'toggle' == toggle ) {
        toggle = ( element.classList.contains( 'slide-up-down-hide' ) ? 'show' : 'hide' );
    }

    const transitionDuration = speed/1000;
    element.style.transitionDuration = transitionDuration + 's';

    if( 'show' == toggle ) {
        setTimeout( function() { 
            element.style.visibility= 'visible';
            element.classList.remove( 'slide-up-down-hide' );
            element.style.maxHeight = maxHeight + 'px';
            element.classList.add( 'slide-up-down-show' );
        }, 10 );
    } else {
        setTimeout( function() { 
            element.classList.add( 'slide-up-down-hide' );
            element.style.maxHeight = '0px';
            element.classList.remove( 'slide-up-down-show' );
            setTimeout( function() { 
                element.style.visibility= 'hidden';
            }, speed + 10 );
        }, 10 );
    }

}





    /**
     * Slide Down or Slide Up an element in Vanilla JS without using jQuery
     * 
     * @param { element } element 
     * @param { string | bool } direction can be true|false or 'down'|'up'
     * @param { int } maxHeight The maxheight of the element if shown.
     *                          Don't set it too much above expected height for a nice transition.
     * @return { bool } current status of the element. if visible return true, else return false. 
     */
    /* UNUSED */
    /*
    display.slideUpDown = function( element, direction = 'down', maxHeight = 300 ) {
        element.classList.remove( 'hide' );
        // element.style.maxHeight = '300px';
        //direction = ( element.classList.contains( 'hide' ) ) ? true : false;
        if( direction ) { // show

            console.log( 'show: ' + direction);
            //console.log( element );
            element.classList.remove( 'hide' );
            console.log( element );
            //element.style.display = 'block';
            //element.style.maxHeight = 300 ;
            // element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            setTimeout( function( ) {
                console.log( 'settimeout');

                element.style.transition = 'max-height 0.4s ease 0'; 
                element.style.maxHeight = '300px';
                console.log( element );
            }.bind( element ), 1);
           //console.log( element );

        } else { // hide
            console.log( 'show: ' + direction);
            console.log( element );
            // element.addEventListener( 'transitionend', onTransitionComplete );
            element.style.transition = 'max-height 0.4s ease 0'; 
            element.style.maxHeight = '0px';
            setTimeout( function( ) {
               //console.log( element );
                element.classList.add( 'hide' );
                //element.style.removeProperty("maxHeight");

                console.log( element );
            }.bind( element ), 2000 );
           // 
            //element.style.removeProperty("display");



        }
        return;
        console.log( 'start' );
       //  console.log( element );
        // add on first run a transition listener. Add class 'hide' at end of transition to hide element completely.
        if( ! element.getAttribute('data-listener-transitionend') ) {
            element.addEventListener( 'transitionend', ( event ) => {
                console.log( 'mxH: ' + maxHeight );
                console.log( event.target.style.maxHeight );
                if( '0px' === event.target.style.maxHeight ) {
                    event.target.style.display = 'none';
                }  


//                const visible = ( '0px' === event.target.style.maxHeight ) ? false : true;
//                 if( ! visible ) {
//                     event.target.classList.add( 'hide' );
//                 }

            });
            element.setAttribute('data-listener-transitionend', true);
        }

        direction = ( true === direction || 'down' === direction ) ? true : false;

        if( direction ) {
            console.log( 'mxH: ' + maxHeight );
            element.classList.remove( 'hide' );
            setTimeout( function() {   // setting "display: block" ruins transition, so start transition 1 ms later.

                element.style.maxHeight = maxHeight + 'px';
            }.bind( element ), 1 );
        } else {
            element.style.maxHeight = '0';
        }
        console.log( element );
        return direction;
    };

    */

    /* only on hide */
    /* UNUSED
    function onTransitionComplete( event ) {
        console.log( 'transitionComplete ');
        console.log( event.target );
        event.target.removeEventListener( 'transitionend', onTransitionComplete );
        event.target.style.display = 'none';
        //event.target.style.removeProperty("max-height");
        console.log( event.target );

    }
*/





   /**
     * Toggle a popup between visible and invisible
     * @param { string } id:The ID of the popup to be shown
     * @param { int } posX,posY:The coordinates of the click,
     *               to position the popup right next to or beneath the clicked position
     */
    display.popupToggle = function( id, posX = 0, posY = 0, fullscreen = false ) {
        const popup = document.getElementById( id );
        if( popup.classList.contains( 'is-visible' ) ) {
            popup.classList.remove( 'is-visible' );
            setTimeout( function(){
                popup.style.display = 'none';
            },600 );
        } else {
            popup.style.display = 'block';
            let bodyWidth = Math.max( document.body.scrollWidth,document.body.offsetWidth );
            if( fullscreen ) {
                console.log( popup);
                let screenWidth = Math.max( document.body.scrollWidth,document.body.offsetWidth );
                popup.style.right = '1em';
                popup.style.left = 'auto';
                popup.style.height = 'auto'; // ( window.innerHeight * .95 ) + 'px';
                if(bodyWidth < 300 ) {
                    popup.style.width = ( window.innerWidth * .95 ) + 'px';
                } else {
                    popup.style.width = ( window.innerWidth * .75 ) + 'px';
                    popup.querySelector( '.popup-contents' ).style.marginTop = '2em';
                }
            } else if( posX > 0 ) {

                if( posX + ( 300 / 2 ) < bodyWidth ) { // Default popup width == 300
                    let newPosX = posX / 2 + window.scrollX;
                    popup.style.right = 'auto';
                    popup.style.left = newPosX + 'px';
                } else {
                    popup.style.right = '0';
                    popup.style.left = 'auto';
                }
            }
            let newTop = ( posY > 0 ) ? 10 + posY + window.scrollY : 20 + window.scrollY ;
            popup.style.top = newTop  + 'px';
            popup.classList.add('is-visible');
        }
    };



   /**
     * Show a popup for a shorttime
     */
    display.popupShowShort = function( id, posX = 0, posY = 0, fullscreen = false, target = '', touchScreen = false ) {
        if( ! target ) {
            target = document.body;
        }
        const boundingClientRect = target.getBoundingClientRect();
        let left = ( 0 === posX ) ? boundingClientRect.left + ( boundingClientRect.width * 0.1 ) : posX;
        let top = ( 0 === posY ) ? boundingClientRect.top + ( boundingClientRect.height * 0.2 ) : posY;
        let popupShort;
        // event.sourceCapabilities.firesTouchEvents == true if touch event has fired
        popupShort = ( touchScreen )
                    ? document.getElementById( id + '-mobile' )
                    : document.getElementById( id );
        popupShort.style.left = left + 'px';
        popupShort.style.top = top + 'px';
        popupShort.style.display = 'block';
        popupShort.classList.add('is-visible');
        setTimeout( function() {
            popupShort.classList.remove('is-visible');
            setTimeout( function() {
                popupShort.style.display = 'none';
            }, 2000);
        }, 3000 );

    }







    /**
     * responsive:move some elements on smaller screens
     */
    display.responsivePositions= function() {
        if ( window.innerWidth < 600 ) { // move text block to end on smaller screens
            document.getElementById('share-container')
                        .insertAdjacentElement("beforebegin", document.getElementById('qena-container') );
            //goToTop = false;
            // CL.maps.moveLegend( 'up' );
        } else { // move text block (back) on larger screens
            document.getElementById('charts-container-wrapper')
                        .insertAdjacentElement("beforeend", document.getElementById('qena-container') );
            //goToTop = true;
            // CL.maps.moveLegend( 'up' );
        }
        return;
    };



}( window.CL.display = window.CL.display || {} ) );
/** User Experience Object / Namespace: display */








/**
 * Data Handling Object: - cookies, ajax
 * Namespace: dataUtils
 */
( function ( dataUtils, CL ) {
    
    /**
     * Gets the value or values of a javascript cookie.
     * @param { string } key:The name of the cookie.
     * @return:the value of the cookie. If key is empty,return all the cookies as an object.
     */
    dataUtils.getCookie = function( key = '' ) {
        // console.log( document.cookie);
        const cookies = {};
        document.cookie.split(';').forEach( function( item ) {
            let [name,value] = item.split('=');
            if( ( undefined !== name ) && ( undefined !== value ) ) {
                name = name.trim();
                cookies[ name ] = value.trim();
            }
        });
        return ( '' === key ) ? cookies :cookies[ key ];
    };

    /**
     * Write a javascript cookie with value pair key and value.
     * Multiple cookies can be set by calling this function multiple times.
     * Notice:The cookie is standard set for one year.
     * @param { String } key
     * @param { Mixed } value
     */
    dataUtils.setCookie = function( key,value ) {
        let cookieString = '';
        let cookies = dataUtils.getCookie();
        cookies[ key ] = value;
        Object.keys( cookies ).forEach( function( key ) {
            cookieString += key + '=' + cookies[ key ] + '; ';
        });
        const expireDateTime = new Date();
        expireDateTime.setFullYear( expireDateTime.getFullYear() + 1 );
        cookieString += 'expires=' + expireDateTime.toUTCString() + '; path=/';
        document.cookie = cookieString;
    };

    /**
     * Ajax Requests
     * TODO:https://medium.com/coding-design/writing-better-ajax-8ee4a7fb95f
     */
    dataUtils.ajaxRequest = function ( action, category, values ) {
        // let zoomIn = true; // tijdelijke definitie. Hoort bij CLchart
        CL.maps.clearblinkCity();  // remove blinking before new city selected

        if ( event ) event.preventDefault(); // TODO:change onclicks to event listeners
        const title = document.querySelector( '#title' );
        title.innerHTML = "Loading...";
        //clearblinkCity();

        const formData = new FormData();
        formData.append( 'action', action );
        formData.append( 'category', category );
        formData.append( 'value', values );
        formData.append( 'referrer', window.location.href );
        // console.log( "ajax params:" );console.log( formData );

        // send AJAX request
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then( function( headers ) { // read headers
            if( 200 === headers.status ) return headers.text();
            else console.log( 'error fetching data: ' + headers.status  );
        }) 
        .then( function( responseJSON ) { // if headers == OK, read body
            let response;
            // console.log( responseJSON );
            try { 
                response = JSON.parse( responseJSON );
                 //console.log( response.chartLabels );
            } catch ( exception ) {
                if( ! responseJSON ) console.log( '>>> Empty response from server: probably returned \'false\'');
                console.log( 'Unexpected response from server: \n' + responseJSON );
                console.log( "Exception: " + exception );
                return;
            }
            CL.main.resolveAjaxRequest( action,response );
        });
    };


}( window.CL.dataUtils = window.CL.dataUtils || {}, window.CL ) );
/** Data Handling & Support Methods / Namespace: dataUtils */








/**
 * Corona Lokaal Charts Namespace for all charts
 * Namespace: CL.charts
 */

( function ( charts, CL ) {
    // const hideChartReported = true;


    let containerWasteWater, containerInfectionRadarPositiveTests, containerHospitalized; // container for some charts. Can slide up and down
    let wastewater = {};    // holds chart data for waste water charts
    let infectionRadarPositiveTests = {};    // holds chart data for infection radar positive tests  charts
    let city = {};      // holds data for charts for a city
    let hospitalized;
    let chartLabels = [];   // holds the chart labels.
    // let allCharts; 
    let canvasReported,canvasHospitalized,canvasDeceased,canvasWasteWater,canvasInfectionRadarPositiveTests;
    let allCanvasses, allCharts; // array of all the charts
    let yMaxGlobal; // holds max for the y-axis (usually the max country value), so when clicking through waste water charts of provinces or country, the scale of y-axis doesn't change.
    //let delayChartWasteWater = true; // boolean to delay showing waste water chart if it is visible from the start. 

    // zoom control
    let chartSettingsZoomDays,chartResetButton,chartAnnotationsButton,zoomSymbolsList;
    let zoomDays = 50;
    let zoomIn = true;
    let zoomLevel = -1;
    let showAnnotations = 1;




    // some settings for navigating the map using touchscreens

 
    // Setup charts configurations. See documentation for Chart.js: https://www.chartjs.org/docs/latest/configuration/
    function getDefaultChartSettings() {
        // for small screens
        let aspectRatio = ( window.innerWidth < 600 ) ? 1.7 : 2;
        return {
            type:'bar',
            options:{
                tag:'',     // edit kalahiri. Tag can be used to find the chart by tag
                annotation: { // https://github.com/chartjs/chartjs-plugin-annotation/tree/1ab782afce943456f958cac33f67edc5d6eab278
                    annotations: [],
                },
                responsive:true,
                maintainAspectRatio:true,
                aspectRatio:aspectRatio,
                title:{
                    display:true,
                    text:''
                },
                legend:{
                    display:false
                },
                tooltips:{
                    mode:'index',
                    intersect:true,
                    filter:function ( tooltipItem, data ) {
                        if ( data.datasets[tooltipItem.datasetIndex].label == 'hidden' ) return false;
                        return true;
                    },

                    callbacks: {
                        label: function(tooltipItem, data) {
                            var label = data.datasets[tooltipItem.datasetIndex].label || '';

                                if (label) {
                                    label += ': ';
                                }
                                if( data.datasets[ 1 ].label === dataWasteWater.datasets[1].label ) {
                                    label += Math.round(tooltipItem.yLabel );
                                } else {
                                    label += tooltipItem.yLabel;
                                }
                                return label;
                            }
                        }
                },
                scales:{
                    xAxes:[{
                        type: 'category',
                    }],
                    yAxes:[{
                        afterTickToLabelConversion: function( scale ) {
                            if( scale.options.ticks.max === undefined ) return; // flag so update knows a special action has to be taken: remove highest y-axis tick
                            // set the last (highest value) tick to null so it does not display.
                            scale.ticks[0] = null;
                            // need to do the same thing for this similiar array which is used internally
                            scale.ticksAsNumbers[0] = null;
                        },
                        ticks:{
                            precision:0,
                            suggestedMin:0,
                            min:0, // force only positive values
                            // beginAtZero:true
                            //steps:10,
                            //stepValue:5,
                           //  max:4000
                        }
                    }]
                },
            }
        };
    }
    let dataReported = {
        datasets:[{
            type:'line',
            label:'7-daags gemiddelde',
            borderColor:'rgba(0,69,102,1)',
            borderWidth:2,
            fill:false,
            pointRadius:0
        },{
            type:'bar',
            label:'nieuwe meldingen per dag',
            backgroundColor:'rgba(54,156,210,0.5)'
        }]
    };
    let dataHospitalized = {
        datasets:[{
            type:'line',
            label:'7-daags gemiddelde',
            borderColor:'rgba(240,0,0,1)',
            borderWidth:2,
            fill:false,
            pointRadius:0
        },{
            type:'bar',
            label:'nieuwe ziekenhuisopnames per dag',
            backgroundColor:'rgba(54,156,210,0.5)'
        }]
    };
    let dataDeceased = {
        datasets:[{
            type:'line',
            label:'7-daags gemiddelde',
            borderColor:'rgba(0,0,0,1)',
            borderWidth:2,
            fill:false,
            pointRadius:0
        },{
            type:'bar',
            label:'nieuwe overlijdens per dag',
            backgroundColor:'rgba(151,72,6,0.3)'
        }]
    };
    let dataWasteWater = {
        datasets:[{
            type:'line',
            label:'hidden',
            borderColor:'rgb(0,58,105)',
            borderWidth:2,
            fill:false,
            pointRadius:0,
            spanGaps:true
        },{
            type:'bar',
            label:'aantal virusdeeltjes per 100.000 inwoners',
            backgroundColor:'rgba(0,58,105,0.5)'
        }]
    };
    let dataInfectionRadarPositiveTests = {
        datasets:[{
            type:'line',
            label:'7-daags gemiddelde',
            borderColor:'rgba(100,40,0,0.8  )', //'rgba(240,100,0,1)',
            borderWidth:2,
            fill:false,
            pointRadius:0
        },{
            type:'bar',
            label:'percentage positieve tests',
            backgroundColor:'rgba(77,125,165,0.6)'
        }]
    };




    let annotations = [ // https://github.com/chartjs/chartjs-plugin-annotation/tree/1ab782afce943456f958cac33f67edc5d6eab278
        {
            value: "1/1",
            index: 293,
            zoomLevel: 0,
            heightAdjust:0,
            beforeTick: true, // true: between ticks before the selected value or index, false: on the tick of the selected value or index'
            borderColor: 'rgba(0,0,0,0.3)',
            fontColor: "#999",
            text: "2021",
        },
        {
            value: "23/3",
            index: 9,
            zoomLevel:1,
            heightAdjust:0,
            beforeTick: true,
            text: "'Intelligente lockdown'",
        },
        {
            value: "1/6",
            index: 80,
            zoomLevel:1,
            heightAdjust:40,
            beforeTick: true,
            text: "Versoepelen",
        },
        {
            value: "1/6",
            index: 80,
            zoomLevel:1,
            heightAdjust:55,
            beforeTick: true,
            text: "'intelligente lockdown'",
        },
        {
            value: "20/9",
            index: 190,
            zoomLevel:3,
            heightAdjust:40,
            beforeTick: true,
            text: "Horeca uurtje eerder dicht",
        },
        {
            value: "14/10",
            index: 214,
            zoomLevel:1,
            heightAdjust:20,
            text: "'Gedeeltelijke lockdown'",
        },
        {
            value: "15/12",
            index: 274,
            zoomLevel:2,
            heightAdjust:40,
            text: "'Harde lockdown'",
        },
        {
            value:"6/1",
            index:298,
            zoomLevel:2,
            heightAdjust:60,
            text: "start vaccinaties",
        },
        {
            value:"23/1",
            index:315,
            zoomLevel:1,
            heightAdjust:40,
            text:"Avondklok",
        },
        {
            value: "15/2",
            index: 338,
            zoomLevel:2,
            heightAdjust:20,
            text: "Alfa-variant dominant",
        },
        {
            value:"28/4",
            index:410,
            zoomLevel:2,
            beforeTick: true,
            heightAdjust:0,
            text:"Einde avondklok, terrassen open",
        },
        {
            value: "25/6",
            index: 468,
            zoomLevel:1,
            heightAdjust:20,
            text: "'Dansen met Janssen'",
        },
        {
            value: "23/6",
            index: 466,
            zoomLevel:2,
            heightAdjust:60,
            text: "Delta-variant dominant",
        },
        {
            value: "9/7",
            index: 482,
            zoomLevel:2,
            heightAdjust:0,
            text: "Nachtleven weer dicht",
        },
        {
            value: "29/8",
            index: 533,
            zoomLevel:3,
            heightAdjust:20,
            text: "85% van 18+ gevaccineerd",
        },
        {
            value: "25/9",
            index: 550,
            zoomLevel:1,
            heightAdjust: 40,
            beforeTick:true,
            text: "Invoering 3G",
        },
        {
            value: "25/9",
            index: 550,
            zoomLevel:2,
            heightAdjust: 55,
            beforeTick:true,
            text: "Einde 1,5 meter",
        },
        {
            value: "18/11",
            index: 614,
            zoomLevel:3,
            heightAdjust: 0,
            text: "Start boostercampagne",
        },
        {
            value: "14/11",
            index: 610,
            zoomLevel:2,
            heightAdjust: 20,
            beforeTick:true,
            text: "Horeca om 20.00 uur dicht",
        },
        {
            value: "28/11",
            index: 624,
            zoomLevel:2,
            heightAdjust: 40,
            beforeTick:true,
            text: "Avond-lockdown",
        },
        {
            value: "19/12",
            index: 645,
            zoomLevel: 1,
            heightAdjust: 60,
            beforeTick: true,
            text: "Omikron-lockdown",
        },
        {
            value: "28/12",
            index: 654,
            zoomLevel: 2,
            heightAdjust: 40,
            text: "Omikron dominant",
        },
        {
            value: "1/1",
            index: 658,
            zoomLevel: 0,
            heightAdjust:0,
            beforeTick: true,
            borderColor: 'rgba(0,0,0,0.3)',
            fontColor: "#999",
            text: "2022",
        },
        {
            value: "10/1",
            index: 667,
            zoomLevel:3,
            heightAdjust: 20,
            beforeTick:true,
            text: "Scholen open",
        },
        {
            value: "15/1",
            index: 672,
            zoomLevel:3,
            heightAdjust: 40,
            beforeTick:true,
            text: "Winkels open",
        },
        {
            value: "26/1",
            index: 683,
            zoomLevel:2,
            heightAdjust: 0,
            beforeTick:true,
            text: "Alles open tot 22.00",
        },
        {
            value: "18/2",
            index: 706,
            zoomLevel:3,
            heightAdjust:40,
            beforeTick:true,
            text: "Alles open",
        },
        {
            value: "18/2",
            index: 706,
            zoomLevel:3,
            heightAdjust:55,
            beforeTick:true,
            text: "tot 01.00",
        },
        {
            value: "25/2",
            index: 713,
            zoomLevel:2,
            heightAdjust: 0,
            beforeTick:true,
            text: "Alles open",
        },
        {
            value: "27/2",
            index: 715,
            zoomLevel:3,
            heightAdjust: 20,
            beforeTick:true,
            text: "Carnaval",
        },
        {
            value: "23/3",
            index: 739,
            zoomLevel:1,
            heightAdjust: 10,
            beforeTick:true,
            text: "Einde",
        },
        {
            value: "23/3",
            index: 739,
            zoomLevel:1,
            heightAdjust: 25,
            beforeTick:true,
            text: "coronamaatregelen",
        },
        {
            value: "1/1",
            index: 1023,
            zoomLevel: 0,
            heightAdjust:0,
            beforeTick: true,
            borderColor: 'rgba(0,0,0,0.3)',
            fontColor: "#999",
            text: "2023",
        },
        {
            value: "1/1",
            index: 1388,
            zoomLevel: 0,
            heightAdjust:0,
            beforeTick: true,
            borderColor: 'rgba(0,0,0,0.3)',
            fontColor: "#999",
            text: "2024",
        },
        {
            value: "1/1",
            index: 1754,
            zoomLevel: 0,
            heightAdjust:0,
            beforeTick: true,
            borderColor: 'rgba(0,0,0,0.3)',
            fontColor: "#999",
            text: "2025",
        },

    ];


    /**
     * DOM content is loaded so select elements and add event listeners
     */
    charts.initDOMLoaded = function() {
        canvasWasteWater = document.getElementById('canvas-waste-water');
        canvasReported = document.getElementById('canvas-reported');
        canvasHospitalized = document.getElementById('canvas-hospitalized');
        canvasDeceased = document.getElementById('canvas-deceased');
        canvasInfectionRadarPositiveTests = document.getElementById('canvas-infection-radar-positive-tests');
        allCanvasses = [ canvasReported, canvasHospitalized, canvasDeceased, canvasWasteWater, canvasInfectionRadarPositiveTests ];
        // allCanvasses = [ canvasWasteWater, canvasHospitalized, canvasDeceased ];

        zoomSymbolsList = document.querySelectorAll('.zoom-container .zoom');  // array of magnifier symbols
        chartSettingsZoomDays = document.getElementById('chart-setting-zoom-days' );  // input setting zoom days
        chartResetButton = document.querySelectorAll('.zoom-container .chart-reset');  // array of chart reset buttons
        //chartAnnotationsButton = document.querySelectorAll('.zoom-container .chart-annotations');  // array of chart reset buttons
        chartAnnotationsButton = document.getElementById('chart-annotations-button');  // array of chart reset buttons

        containerWasteWater = document.getElementById('container-waste-water'); // container with chart for waste water chart.
        containerHospitalized = document.getElementById('container-hospitalized'); // container with chart for waste water chart.
        containerInfectionRadarPositiveTests = document.getElementById('container-infection-radar-positive-tests'); // container with chart for infection radar positive tests.

        // get the cookies to determine some settings
        const cookieZoomDays = CL.dataUtils.getCookie( 'zoomDays' );
        if ( cookieZoomDays > 2  && cookieZoomDays < 250 ) {
            zoomDays = cookieZoomDays;
        }
        chartSettingsZoomDays.value = zoomDays;

        // add chart settings box on click settings icon.
        /*
        document.getElementById('submit-chart-settings').addEventListener( 'click',function () {
            setZoomDays( chartSettingsZoomDays.value );
        });
        */

        // add zoom action to charts.
        document.querySelectorAll( '.zoom' ).forEach( item => {
            item.addEventListener('click',function() {
                charts.zoom();
            });
        });


        document.getElementById('save-button').addEventListener( 'click', ( event ) => {
            event.preventDefault();
            const canvasElement = document.getElementById( 'canvas-waste-water' );
            CL.interactions.saveCanvasAsImage( "grafiek.rioolwaterwaarden", canvasElement );
            const targetPopup = event.target.closest( '.popup' );
            CL.display.popupToggle ( targetPopup.id );
        });

        document.getElementById('label-chart-setting-zoom-days').addEventListener( 'click', ( event ) => {
            event.preventDefault();
            chartSettingsZoomDays.focus();
            chartSettingsZoomDays.select();
        });
        chartSettingsZoomDays.addEventListener( 'click', ( event ) => {
            chartSettingsZoomDays.focus();
            chartSettingsZoomDays.select();
        });
        chartSettingsZoomDays.addEventListener( 'blur', function( event ) {
                setZoomDays( chartSettingsZoomDays.value );
                chartSettingsZoomDays.blur();
                const targetPopup = event.target.closest( '.popup' );
                CL.display.popupToggle ( targetPopup.id );
        });
        chartSettingsZoomDays.addEventListener( 'keydown', function( event ) {
            if ( 'Tab' === event.key ) {
                event.preventDefault();
                setZoomDays( chartSettingsZoomDays.value );
                chartSettingsZoomDays.blur();
                const targetPopup = event.target.closest( '.popup' );
                CL.display.popupToggle ( targetPopup.id );
            }
            if ( 'Enter' === event.key ) {
                setZoomDays( chartSettingsZoomDays.value );
                chartSettingsZoomDays.blur();
                const targetPopup = event.target.closest( '.popup' );
                CL.display.popupToggle ( targetPopup.id );
            }
        });

        // chartAnnotationsButton.forEach( item => {
        chartAnnotationsButton.addEventListener( 'click', ( event ) => {
            event.preventDefault();
            showAnnotations++
            if( showAnnotations > 2 ) showAnnotations = 0;
            syncAllCharts();
        });

        // add reset action to charts.
        chartResetButton.forEach( item => {
            item.addEventListener('click',function() {
                reset();
            });
        });
        document.getElementById('total-chart-button').addEventListener( 'click', ( event ) => {
            event.preventDefault();
            reset();
            const targetPopup = event.target.closest( '.popup' );
            CL.display.popupToggle ( targetPopup.id );
        });

        document.getElementById('toggle-chart-hospitalized').addEventListener( 'click', ( event ) => {
            event.preventDefault();
            CL.display.slideUpDown( containerHospitalized, 'toggle', 400, 100, containerWasteWater ) ;
        });



        // add chart settings box on click settings icon.
        document.querySelectorAll( '.chart-settings' ).forEach( item => {
            item.addEventListener( 'click',event => {
                CL.display.popupToggle ( 'popupChartSettings',event.clientX,event.clientY );

            });
        });

        /*
        chartSettingsZoomDays.addEventListener( 'keypress',function( event ) {
            if ( 'Enter' === event.key ) {
                const targetPopup = event.target.closest( '.popup' );
                CL.display.popupToggle( targetPopup.id );
                setZoomDays( chartSettingsZoomDays.value );
                chartSettingsZoomDays.blur();
            }
        });
        */


        // add close action to waste water chart so it can be hidden
        let buttonCloseWasteWaterChart = document.getElementById( 'close-chart');
        //console.log( buttonCloseWasteWaterChart );
        if( buttonCloseWasteWaterChart ) {
            buttonCloseWasteWaterChart.addEventListener('click',event => {
                // const target = event.target.closest( '.canvas-container' );
                // console.log( target );
                CL.maps.hide( 'waste-water' );
                // CL.display.popupToggle ( targetPopup.id );
            });
        }

        allCanvasses.forEach( function( canvas ) {
            if( canvas ) {
                canvas.addEventListener("wheel", zoomAction);
                canvas.addEventListener('mousedown', event => { panAction( event, 'start' ) });
                canvas.addEventListener('mousemove', event => { panAction( event, 'panning' ) });
                canvas.addEventListener('mouseup', event => { panAction( event, 'end' ) });
                canvas.addEventListener('touchstart', event => { panAction( event, 'start' ) });
                canvas.addEventListener('touchmove', event => { panAction( event, 'panning' ) });
                canvas.addEventListener('touchend', event => { panAction( event, 'end' ) });

                canvas.addEventListener('mousemove', interactionHelp );

            }
        });

    }; // DOMContentLoaded


    /**
     * Event window loaded: All elements for teh webpage are loaded
     * Draw the charts as soon as the page is loaded.
     */
    charts.initWindowLoaded = function() {
        // create the background color for the charts
        /*
        Chart.register({
            id: 'custom_canvas_background_color_white',
            beforeDraw:function (chart )  {
                const ctx = chart.canvas.getContext('2d');
                ctx.save();
                ctx.globalCompositeOperation = 'destination-over';
                if( ctx.canvas.id == 'canvas-waste-water') {
                    ctx.fillStyle = '#fff7e9';
                } else {
                    ctx.fillStyle = '#fff';
                }
                ctx.fillRect(0, 0, chart.width, chart.height);
                ctx.restore();
            }
        });
        */


        Chart.plugins.register({
            beforeDraw:function ( chartInstance ) {
                const ctx = chartInstance.chart.ctx;
                if( ctx.canvas.id == 'canvas-waste-water') {
                    ctx.fillStyle = '#fff7e9';
                } else if( ctx.canvas.id == 'canvas-infection-radar-positive-tests') {
                    ctx.fillStyle = '#fff'; // '#f2f9ff';
                } else {
                    ctx.fillStyle = '#fff';
                }
                ctx.fillRect(0,0,chartInstance.chart.width,chartInstance.chart.height);
            }
        });
    
    


        // draw the charts 'Waste Water'
        const chartWasteWaterSettings = Object.assign({},getDefaultChartSettings(),{ data:dataWasteWater });
        window.chartWasteWater = new Chart( canvasWasteWater.getContext('2d'),chartWasteWaterSettings );
        window.chartWasteWater.options.tag = 'chartWasteWater'; // used to find the chart by tag name.
        window.chartWasteWater.update();
        // draw the chart 'Reported'
        const chartReportedSettings = Object.assign({},getDefaultChartSettings(),{ data:dataReported });
        window.chartReported = new Chart( canvasReported.getContext('2d'),chartReportedSettings );
        // draw the chart 'Hospitalized'.
        const chartHospitalizedSettings = Object.assign({},getDefaultChartSettings(),{ data:dataHospitalized });
        window.chartHospitalized = new Chart( canvasHospitalized.getContext('2d'),chartHospitalizedSettings );
        // draw the chart 'Deceased'.
        const chartDeceasedSettings = Object.assign({},getDefaultChartSettings(),{ data:dataDeceased });
        window.chartDeceased = new Chart( canvasDeceased.getContext('2d'),chartDeceasedSettings );
        // draw the chart 'InfectionRadar PositiveTests'.
        const chartInfectionRadarPositiveTestsSettings = Object.assign({},getDefaultChartSettings(),{ data:dataInfectionRadarPositiveTests });
        window.chartInfectionRadarPositiveTests = new Chart( canvasInfectionRadarPositiveTests.getContext('2d'),chartInfectionRadarPositiveTestsSettings );
        window.chartInfectionRadarPositiveTests.options.layout.padding.left = 20; // set some padding to line up the different charts.
        window.chartInfectionRadarPositiveTests.update();
        // allCharts = [ window.chartReported, window.chartHospitalized, window.chartDeceased, window.chartWasteWater ];
        allCharts = [ window.chartWasteWater, window.chartHospitalized, window.chartDeceased, window.chartInfectionRadarPositiveTests ];



        // click assist "klik dit eens aan!"
        setTimeout( function(){
            const clickAssist = document.getElementById('click-assist');
            if( null !== clickAssist ) clickAssist.style.display = 'none';
        }, 3000 );

    }; // windowLoaded




    function selectAnnotationsToShow() {
        let annotationsArray = [];
        annotations.filter( values => zoomLevel >= values.zoomLevel ).forEach( function( values ) {
            let annotation = {
                drawTime: "afterDatasetsDraw",
                type: "line",
                mode: "vertical",
                scaleID: "x-axis-0",
                value: "22/1",
                beforeTick: false,
                borderWidth: 1,
                borderColor: "red",
                label: {
                        enabled: true,
                        position: "top",
                        backgroundColor: 'rgba(255,255,255,0.75)',
                        fontSize: 10,
                        fontColor: "#777",
                        yAdjust:-20
                      },
            };

            annotation.label.content = values.text;
            annotation.index = values.index;
            annotation.heightAdjust = values.heightAdjust + 20;
            if( typeof values.beforeTick !== 'undefined' ) annotation.beforeTick = values.beforeTick;
            if( typeof values.borderColor !== 'undefined' ) annotation.borderColor = values.borderColor;
            if( typeof values.fontColor !== 'undefined' ) annotation.label.fontColor = values.fontColor;

            annotationsArray.push( annotation );
        });
        allCharts.forEach( chart => chart.config.options.annotation.annotations = annotationsArray );
    }








    /**
     * reset all charts to their original ( = full ) time period
     */
    function reset() {
        syncAllCharts( { min: 0, max: city.data.chartLabels.length - 1 } );
        zoomIn = false;
        redrawZoomSymbols();
    }


    /**
     * Zoom charts in or out.
     */
    charts.zoom = function () {
        zoomIn = ! zoomIn;
        showAnnotations = 0;
        if( zoomIn ) {
            let min = city.data.chartLabels.length - zoomDays;
            syncAllCharts( { min: min, max: city.data.chartLabels.length - 1 } );
        } else {
            syncAllCharts( { min: 0, max: city.data.chartLabels.length - 1 }  );
        }
        redrawZoomSymbols(); // redraw the zoom-symbols on the charts
    };


    /**
     * redraw the zoom-symbols on the charts
     */
    function redrawZoomSymbols() {
        const char = ( zoomIn ) ? "&#xf010" :"&#xf00e";
        const info = ( zoomIn ) ? "zoom uit van begin maart 2020 tot nu" :"zoom in en laat alleen de laatste periode zien. \nHet aantal dagen is bij het radartje in te stellen";
        for ( let i = 0; i < zoomSymbolsList.length; ++i ) {
            zoomSymbolsList[i].innerHTML = char;
            zoomSymbolsList[i].title = info;
        }
    }


    /**
     * Set the number of days for the zoom function to zoom in to.
     * @param { integer } days
     */
    function setZoomDays( days = 60 ) {
        days = Math.round( +days );
        if( days < 2 ) {
            days = 2;
        }
        if( days > city.data.chartLabels.length - 1 ) {
            days = city.data.chartLabels.length - 1;
        }
        zoomDays = days;
        CL.dataUtils.setCookie( 'zoomDays',zoomDays );
        let min = city.data.chartLabels.length - zoomDays;
        syncAllCharts( { min: min, max: city.data.chartLabels.length - 1 }  );
        chartSettingsZoomDays.value = zoomDays; // set form field with new value
    }





    /**
     * Zoom in or out on the charts.
     * Currently only available through movement of the mouse wheel
     * 
     * @param {event} event 
     */
    function zoomAction (event) {
        // console.log( 'zoomin' );
        if (event.cancelable) {
            event.preventDefault();
        }
        // Firefox always fires wheel event twice: First without delta and right after once with delta properties.
        if (typeof event.deltaY === 'undefined') {
            return;
        }
        yMaxGlobal = undefined;

         // allCharts = [ window.chartReported, window.chartHospitalized, window.chartDeceased, window.chartWasteWater ];
      
        // const labelLength = window.chartReported.config.data.labels.length - 1;
        const labelLength = allCharts[1].config.data.labels.length - 1;
        // console.log( labelLength );
        // let scale = window.chartReported.scales['x-axis-0'];
        let scale = allCharts[1].scales['x-axis-0'];
        // console.log( scale );
        const currentValues = {
            length: scale.options.ticks.maxIndex - scale.options.ticks.minIndex,
            min: scale.options.ticks.minIndex,
            max: scale.options.ticks.maxIndex
        }
        // console.log( currentValues );
        let wheelMove = ( event.deltaY > 0 ) ? -1 : 1;  // zoom in : 1, zoom out : -1
        // determine mouse position on mouse wheel action
        let mousePosition = panzoomPositionOnChart( allCharts[1], event );
        if ( ! mousePosition.onChart ) return false; // mouse click outside chart
        let steps = Math.round( currentValues.length / 10 );
        steps = Math.max( steps, 5 );

        // calculate new values for zoom in or zoom out
        let newValues = {}
        if ( wheelMove < 0 ) {   // zoom out
            newValues.length = Math.round( currentValues.length + steps );
            if( mousePosition.position == 'center' ) { // mouse position: center
                newValues.min =  currentValues.min - Math.round( steps / 2 );
            } else if( mousePosition.position == 'left' ) { // mouse position: left
                newValues.min = currentValues.min;
            } else { // mouse position: right
                newValues.min = currentValues.min - steps;
            }
        } else {  // zoom in
            newValues.length = Math.round( currentValues.length - steps );
            if( mousePosition.position == 'center' ) { // mouse position: center
                newValues.min =  currentValues.min + Math.round( steps / 2 );
            } else if( mousePosition.position == 'left' ) { // mouse position: left
                newValues.min = currentValues.min;
            } else { // mouse position: right
                newValues.min = currentValues.min + steps;
            }
        }
        newValues = panzoomRangeLimiter( newValues.min, newValues.length, labelLength );
        // console.log( newValues );
        syncAllCharts( newValues );
        return;
    }

    let chartArea = {};
    let pan = {
        panning: false
    };
    /**
     * Panning of the charts to the left or the right.
     * At the moment only available on mouse click/movement
     * 
     * @param {*} event 
     * @param {*} status 
     */
    const currentTouchPosition = {}
    function panAction( event, status ) {
        if ('MouseEvent' === event.constructor.name && event.cancelable) {
            event.preventDefault();
        }
        if ( 'TouchEvent' === event.constructor.name && event.touches.length > 1) {  // Ignore multi ('two-fingers') touch events
            return;
        }
        yMaxGlobal = undefined;


        if( 'panning' == status && pan.panning ) {
            if( 'TouchEvent' === event.constructor.name ) {
                if( 'touchmove' === event.type ) {
                    event.movementX = event.targetTouches[0].pageX - currentTouchPosition.PageX;
                    //event.movementY = touch.pageY - previousTouch.pageY;
                    currentTouchPosition.PageX = event.targetTouches[0].pageX;
                    event.buttons = 1;
                } else {
                    return;
                }
            }
            if( event.buttons == 1 ) {
                pan.deltaX += event.movementX; // sum of mousemouvement
                if( Math.abs(pan.deltaX) > chartArea.barWidth ) { // mousemovent large enough? => panning
                    let deltaIndex = Math.round( pan.deltaX / chartArea.barWidth );
                    let min =  pan.currentValues.min - deltaIndex;
                    let newValues = panzoomRangeLimiter( min, pan.currentValues.length, pan.labelLength );

                    syncAllCharts( newValues );
                    pan.currentValues = newValues;
                    pan.deltaX = 0;
                }
                return;
            } else {
                status = 'end';
            }
        }
        if( 'start' == status ) {
            // console.log( event );
            let mousePosition = panzoomPositionOnChart( allCharts[1], event );
            //console.log( mousePosition );
            //console.log( event );
            if( 'TouchEvent' === event.constructor.name ) {
                currentTouchPosition.PageX = event.targetTouches[0].pageX;
            }
            //console.log( mousePosition );
            if ( ! mousePosition.onChart ) return false; // mouse click outside chart
            event.target.style.cursor = 'ew-resize';

            pan.labelLength = allCharts[1].config.data.labels.length - 1;
            pan.scale = allCharts[1].scales['x-axis-0'];
            pan.currentValues = {
                length: pan.scale.options.ticks.maxIndex - pan.scale.options.ticks.minIndex,
                min: pan.scale.options.ticks.minIndex,
                max: pan.scale.options.ticks.maxIndex
            }
            chartArea.barWidth = ( allCharts[1].chartArea.right - allCharts[1].chartArea.left ) / pan.currentValues.length;
            pan.panning = true;
            pan.offsetX = event.offsetX;
            pan.deltaX = 0;
        }
        if( 'end' == status ) {
            event.target.style.cursor = 'auto'; // reset cursor
            chartArea = {};
            pan = {
                panning: false
            };
        }
        return;
    }


    function interactionHelp( event ) {
        if ('MouseEvent' === event.constructor.name && event.cancelable) {
            event.preventDefault();
        }
        if ( 'TouchEvent' === event.constructor.name && event.touches.length > 1) {  // Ignore multi ('two-fingers') touch events
            return;
        }
        allCharts.forEach( (item) => {
            if( event.target == item.canvas ) {
                CL.display.popupShowShort('popup-chart-help',0,0,false,
                        event.target, event.sourceCapabilities.firesTouchEvents );
                // One time only so remove the eventlisteners after first call.
                item.canvas.removeEventListener('mousemove', interactionHelp );
            }
        });
        /*
        allCharts.forEach( (item) => {
            if( event.target == item.canvas ) {
                const boundingClientRect = item.canvas.getBoundingClientRect();
                const left = boundingClientRect.left + ( boundingClientRect.width * 0.1 );
                const top = boundingClientRect.top + ( boundingClientRect.height * 0.2 );
                let popupShort;
                // event.sourceCapabilities.firesTouchEvents == true if touch event has fired
                popupShort = ( event.sourceCapabilities.firesTouchEvents )
                            ? document.getElementById( 'popup-chart-help-mobile' )
                            : document.getElementById( 'popup-chart-help' );
                popupShort.style.left = left + 'px';
                popupShort.style.top = top + 'px';
                popupShort.style.display = 'block';
                popupShort.classList.add('is-visible');
                setTimeout( function() {
                    popupShort.classList.remove('is-visible');
                    setTimeout( function() {
                        popupShort.style.display = 'none';
                    },2000);
                },3000 );
            }
        });
        // One time only so remove the eventlisteners after first call.
        allCanvasses.forEach( function( canvas ) {
            if( canvas ) {
                canvas.removeEventListener('mousemove', interactionHelp );
            }
        });
        */
    }


    /**
     * Get the position of the mouse on a chart. 
     * Is it on the chart area? If yes, it is on the right, center or on the left
     * 
     * @param {chart} chart 
     * @param {event} event
     * 
     * @return { bool } onChart is true if the mouse is on the chart area 
     * @return { enum } position is the position on the chart: 'left', 'center' or 'right'  
     */
    function panzoomPositionOnChart( chart, event ) {
        //console.log( event.target.getBoundingClientRect() );
        // console.log( event );
        //console.log( '----' );
        if( 'TouchEvent' === event.constructor.name ) {

            const rect = event.target.getBoundingClientRect();
            event.offsetX = event.targetTouches[0].clientX - rect.left;
            event.offsetY = event.targetTouches[0].clientY - rect.top;
            //console.log( rect.top );

        }

        //console.log( event );
        const chartArea = {}
        chartArea.left = chart.chartArea.left;
        chartArea.right = chart.chartArea.right;
        chartArea.top = chart.chartArea.top;
        chartArea.width = chart.chartArea.right - chart.chartArea.left;
        chartArea.height = chart.chartArea.bottom - chart.chartArea.top;
        chartArea.center = {
            x: chartArea.width / 2,
            margin: chartArea.width / 5,    // margin around center is also center
        } 
        let mousePosition = {
            x: event.offsetX - chartArea.left,
            y: event.offsetY - chartArea.top,
        }
        if( mousePosition.x < 0 || mousePosition.x > chartArea.width ||
            mousePosition.y < 0 || mousePosition.y > chartArea.height  ) {
            return { onChart: false, position: '' };         // check if click outside chart area
        }
        if ( mousePosition.x < chartArea.center.x - chartArea.center.margin ) {
            mousePosition.relative = 'left';
        } else if ( mousePosition.x > chartArea.center.x + chartArea.center.margin ) {
            mousePosition.relative = 'right';
        } else mousePosition.relative = 'center';
        return { onChart: true, position: mousePosition.relative };  
    }

            

    /**
     * To prevent the zooming and panning going of the scale.
     * 
     * @param {int} min 
     * @param {int} length 
     * @param {int} maxLength
     * 
     * @return { int } min: the min value of the scale of the chart
     * @return { int } max: the max value of the scale of the chart
     * @return { int } length: the length of the scale of the chart
     */
    function panzoomRangeLimiter( min, length, maxLength ) {
        const values = {};
        length = Math.max( length, 5 );
        values.min = Math.max( min, 0 );
        values.max = values.min + length;
        if( values.max > maxLength ) {
            values.max = maxLength;
            values.min = values.max - length;
            values.min = Math.max( values.min, 0 );
        }
        values.length = values.max - values.min;
        return values;
    }


    /**
     * Synchronize all charts if user zooms or pans one of the charts.
     * @param {object} values containing values.min and values.max to set the scale of the x-axis of the charts
     * @param {bool} animate true / false if charts update should be animated.
     *  
     */                          
    function syncAllCharts( values = {}, animate = true, animationDuration = 200 ) {
        
        allCharts.forEach( function( chart, index ) {
            //if( hideChartReported && 0 == index ) return; // 'return' means 'continue' in a foreach loop
            if( undefined != values.min && undefined != values.max ) {
                let yMax;
                if( 'chartWasteWater' !== chart.config.options.tag ) { // this for all but chart waste water. 
                    // set new max for y-axes depending on new slice of the values
                    //yMax = Math.round( 1.1 * Math.max( ...chart.config.data.datasets[1].data.slice( values.min, values.max + 1 ) ) );
                    yMax = 1.1 * Math.max( ...chart.config.data.datasets[1].data.slice( values.min, values.max + 1 ) ) ;
                    yMax = Math.max( 2.1,1.1 * Math.max( ...chart.config.data.datasets[1].data.slice( values.min, values.max + 1 ) ) );
                } else { // this for chart waste water. 
                    yMax = 0;
                    chart.config.data.datasets[1].data.slice( values.min, values.max + 1 ).forEach( (value) => { 
                        if ( value > yMax ) {
                            yMax = value;
                        }
                    });
                    // console.log( 'value yMax raw: ' + yMax );
                    yMax = Math.max( Math.round( 1.1 * yMax ), 300 );



                    // Try to keep the scale of the y-axis consistent for chart of different regions (province, country) for easy comparison.
                    // Only if one goes to National Chart, the scale of the y-axis is frozen. On zoom or pan the y-axis is released again.
                    /*
                    if( chart.config.options.title.text.length > 0 && chart.config.options.title.text.includes( 'Nederland' ) ) {
                        yMaxGlobal = yMax;
                    }
                    */
                    if( yMaxGlobal > 300 ) yMax = yMaxGlobal; // yMaxGlobal to keep the scale of y-axis the same for chart of different regions  





                }
                
                if( yMax ===  Number.NEGATIVE_INFINITY ) {  // i.e. when waste water chart is not shown.
                    return;
                }
                chart.scales['y-axis-0'].options.ticks.max = yMax;
                
                // set new min and max to determine the slice to create chart from.
                let scale = chart.scales['x-axis-0'];
                scale.options.ticks.minIndex = values.min;
                scale.options.ticks.maxIndex = values.max;
            }


            // Annotations
            let zoomLevelNew;
            if( 0 == index ) { // add annotations to the first chart ( allCharts[0] )
                zoomLevelNew = ( 0 == showAnnotations ) ? 0 : calculateZoomLevel( allCharts[1].scales['x-axis-0'].options.ticks);
            } else {
                zoomLevelNew = ( 2 == showAnnotations ) ? calculateZoomLevel( allCharts[1].scales['x-axis-0'].options.ticks) : 0;
            }
            if( zoomLevelNew != zoomLevel ) {
                zoomLevel = zoomLevelNew;
                selectAnnotationsToShow();
            }
            // Update all charts
            if( animate ) {
                chart.update({
                    duration: animationDuration,
                    easing: 'easeOutQuad',
                });
            } else chart.update(0);
            chart.scales['y-axis-0'].options.ticks.max = undefined; // clear max value to not interfere with update with different city / dataset
       });
    }


    /**
     * Calculates the zoomlevel depening on the number of bars in the visible chart
     * Zoomlevel determines what annotations to show
     * @param { obj } ticks 
     * @return { int } zoomLevel
     */
    function calculateZoomLevel( ticks ) {
        // if ( window.innerWidth < 600 ) return 0; // small screens
        let numberOfBars = ticks.maxIndex - ticks.minIndex;
        return ( numberOfBars > 100) ? ( numberOfBars > 200) ? 1 : 2 : 3;
    }


    /**
     * Synchronize all charts if user zooms or pans one of the charts.
     * @param {int} id 
     */         
    /*                 
    function syncAllCharts( id, min, max ) {
        allCharts.forEach( function( chart ) {
            if( id != chart.id ) {
                chart.zoomScale('x', {min: min, max: max}, 'default');
                chart.update();
            }
        });
    }
    */



    /**
     * Get the name and type of the location if available from the chart data
     */
    charts.getLocation = function() {
        let location ={};
        location.name = ( city.data && city.data.province ) ? city.data.province.pv_name : false;
        location.region = ( city.data && city.data.type && city.data.type.region )
                                        ? city.data.type.region : false;
        return location;
    };


    /**
     * Get the name and type of the waste water location if available from the chart data
     */
    charts.getWastewaterLocation = function() {
        let location ={};
        location.name = ( wastewater.data && wastewater.data.plant && wastewater.data.plant.name )
                                        ? wastewater.data.plant.name : false;
        location.region = ( wastewater.data && wastewater.data.type && wastewater.data.type.region )
                                        ? wastewater.data.type.region : false;
        return location;
    };





    /**
     * Get a specific label from the array of chart labels.
     * Used mainly to get the last available date as being the day the data last updated. Called from CL.main.  
     * @param {enum} type 
     * @param {int} position 
     */
    charts.getChartLabel = function( type, position ) {
        switch( type ) {
            case 'city': 
                if( position < 0 ) return city.data.chartLabels[ city.data.chartLabels.length - 1 ];
            break;
            default: return 0
        }
    };




    /**
     * Get the waste water chart data if available
     */
    charts.getwastewaterData = function() {
        return ( wastewater.data ) ? wastewater.data : false;
    };


    /**
     * Toggle waste water chart: Show or hide the waste water chart. Called from CL.map and CL.main
     * @param {bool} toggle 
     */
    charts.toggleWasteWater = function( toggle = true, onPageLoad = false ) {

        let slideTimer,loadTimer;
        containerWasteWater.classList.remove( 'hide' );
        if( toggle ) {
            if( onPageLoad ) {
                //slideTimer = 750;
                loadTimer = 500;
            } else {
                //slideTimer = 1;
                loadTimer = 500;
            }
            containerWasteWater.style.display = 'block';
            // containerWasteWater.style.overflow = 'visible';
            setTimeout( function() {
                //containerWasteWater.style.maxHeight = ( window.innerWidth < 600 ) ? '400px' : '280px';
                let heightVal;
                if( window.innerWidth > 2000 ) {
                    heightVal = window.innerWidth / 4;
                } else if( window.innerWidth > 1000 ) {
                heightVal = window.innerWidth / 3;
                } else if( window.innerWidth > 600 ) {
                    heightVal = window.innerWidth / 2;
                } else  heightVal = window.innerWidth * 0.7;
                // containerWasteWater.style.maxHeight = window.innerWidth / 4 ) ? '400px' : '280px';
                containerWasteWater.style.maxHeight = Math.round( heightVal ) + 'px';
                if( ! wastewater.data ) {
                    setTimeout( function() {
                        if( ! plant ) plant = 'Nederland';
                        CL.dataUtils.ajaxRequest( 'get', 'plant', plant );
                    }, loadTimer );
                }
            }, 1 );
            setTimeout( function() {
                containerWasteWater.style.overflow = 'visible';
                let scrollToTop = ( window.innerWidth > 600 ) ? true : false;
                CL.display.scrollToTop( scrollToTop );
            }, 500 );
        } else {
                containerWasteWater.style.maxHeight = '1px';
                containerWasteWater.style.overflow = 'hidden';
                setTimeout( function() {
                    containerWasteWater.style.display = 'none';
                }, 400 );
            }
        return;

    };








    /**
     * Set the data received by an ajax-request to the charts.
     * @param {enum} type 
     * @param {array-object} data 
     */
    charts.setChartData = function( type, data ) {
        const zoom = ( chartLabels.length == 0 ) ? true : false;


        // console.log( "setChartData" );
        // console.log( "type: " + type );
        // console.log( "chartLabels" );
        // console.log( chartLabels );
        // console.log( data.chartData );


        switch( type ) {
            case 'wastewater':
                const labelAverage = '7-daags gemiddelde';
                wastewater.data = Object.assign( {}, data );


                if ( wastewater.data && wastewater.data.chartWasteWaterData
                            && wastewater.data.chartWasteWaterData.length > 0 ) {
                             // && city.data.chartLabels && city.data.chartLabels.length > 0 ) {
                    let mappedData = mapWasteWaterData( chartLabels, wastewater.data );   // map the available waste water data-points to the days of the chart labels
                    dataWasteWater.labels = chartLabels;
                    dataWasteWater.datasets[1].data = mappedData;

                    // The second series is a line and can be set to 'seven day average' or to the same values as first series. 
                    if ( 'province' === wastewater.data.type.region || 'country' === wastewater.data.type.region ) { // provincie or country
                        dataWasteWater.datasets[0].data = calculateMovingAverage( dataWasteWater.datasets[1].data );
                        window.chartWasteWater.data.datasets[0].label = labelAverage;

                    } else  {  // rwzi
                        dataWasteWater.datasets[0].data = mappedData;
                    }
                    window.chartWasteWater.options.title.text = 'Virusdeeltjes in waterzuivering ' + wastewater.data.plant.name;



                    

                    // some explanation in the tooltip for country chart
                    if( 'country' === wastewater.data.type.region 
                                && wastewater.data.chartWasteWaterFactor 
                                && wastewater.data.chartWasteWaterFactor.length > 0 ) {
                        const maxRwziFactor = Math.max( ...wastewater.data.chartWasteWaterFactor );
                        //let seriesBaseIndex = ( zoom ) ? chartLabels.length - zoomFactor :0;
                        let showPercentageIndex = dataWasteWater.datasets[0].data.length - 25;
                        window.chartWasteWater.options.tooltips.callbacks.afterLabel = function( tooltipItem, data ) {
                            if( labelAverage === data.datasets[ tooltipItem.datasetIndex ].label ) return;
                            if ( tooltipItem.index > showPercentageIndex ) {
                                // const i = tooltipItem.index + seriesBaseIndex;
                                const i = tooltipItem.index;
                                const val = Math.round( ( wastewater.data.chartWasteWaterFactor[ i ] / maxRwziFactor ) * 100 );
                                if( val > 100 ) return;
                                return ["(met een betrouwbaarheid van " + val + "%)"];
                            }
                        };

                        // set different colors for bars according to reliability of the value
                        dataWasteWater.datasets[1].backgroundColor = [];
                        let currentPercentage;
                        for( let i = 0; i < dataWasteWater.datasets[1].data.length; i++ ) {
                            dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(0,58,105,0.5)';
                            if( i > dataWasteWater.datasets[1].data.length - 30 ) { // check only the last 30 values
                                currentPercentage= wastewater.data.chartWasteWaterFactor[ i ] / maxRwziFactor;
                                if( currentPercentage < 0.6 ) {
                                    dataWasteWater.datasets[0].data[ i ] = null;
                                    if( currentPercentage < 0.2 ) {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.15)';
                                    } else if( currentPercentage < 0.4 ) {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.2)';
                                    } else {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.3)';
                                    }
                                }
                            }

                        }
                    } else if ( 'province' === wastewater.data.type.region 
                                    && wastewater.data.chartWasteWaterReliabilities
                                    && wastewater.data.chartWasteWaterReliabilities.length > 0 ) {
                        // console.log( dataWasteWater.datasets[0].data.length );
                        let showPercentageIndex = dataWasteWater.datasets[0].data.length - 20;
                        window.chartWasteWater.options.tooltips.callbacks.afterLabel = function( tooltipItem, data ) {
                            if( labelAverage === data.datasets[ tooltipItem.datasetIndex ].label ) return;  // return label for serie 'average' unchanged.  
                            if ( tooltipItem.index > showPercentageIndex && wastewater.data.chartWasteWaterReliabilities[ tooltipItem.index ] ) {
                                return ["(met een betrouwbaarheid van " 
                                            + Math.round( wastewater.data.chartWasteWaterReliabilities[ tooltipItem.index ] ) + "%)"];
                            }
                        };
                        // set different colors for bars according to reliability of the value
                        dataWasteWater.datasets[1].backgroundColor = [];
                        let currentPercentage;
                        for( let i = 0; i < dataWasteWater.datasets[1].data.length; i++ ) {
                            dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(0,58,105,0.5)';
                            if( i > dataWasteWater.datasets[1].data.length - 30 ) { // check only the last 30 values
                                currentPercentage= wastewater.data.chartWasteWaterReliabilities[ i ];
                                //console.log( wastewater.data.chartWasteWaterReliabilities[ i ] );
                                if( currentPercentage < 60 ) {
                                    dataWasteWater.datasets[0].data[ i ] = null;
                                    if( currentPercentage < 5 ) {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.0)';
                                        dataWasteWater.datasets[1].data[ i ] = null;

                                    } else if( currentPercentage < 20 ) {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.15)';
                                    } else if( currentPercentage < 40 ) {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.25)';
                                    } else {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.35)';
                                    }
                                }
                            }
                        }


                    } else {
                        dataWasteWater.datasets[1].backgroundColor = [];
                        for( let i = 0; i < dataWasteWater.datasets[1].data.length; i++ ) {
                            dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(0,58,105,0.5)';
                        }
                        window.chartWasteWater.options.tooltips.callbacks.afterLabel = function( tooltipItem ) { return; };
                    }
                    window.chartWasteWater.update();
                }
            break;
            case 'hospitalized':
                city.data = Object.assign( {}, data );
                chartLabels = city.data.chartLabels;

                // hospitalized
                dataHospitalized.labels = chartLabels;
                dataHospitalized.datasets[1].data = city.data.chartData;
                if ( 'country' === city.data.type.region ) {
                    let dataInterpolated = getDataInterpolated( dataHospitalized.datasets[1].data );
                    dataHospitalized.datasets[0].data = calculateMovingAverage( dataInterpolated );
                   // dataHospitalized.datasets[0].data = calculateMovingAverage( dataHospitalized.datasets[1].data );
                } else { // do not use most recent day for average. It is not complete yet
                    dataHospitalized.datasets[0].data = calculateMovingAverage( dataHospitalized.datasets[1].data )
                                                                  .slice( 0,chartLabels.length - 1 );
                }
   
                // NICE-Data for the most recent days are not complete,so we use some special settings
                if ( 'province' === city.data.type.region ) {
                    const last = dataHospitalized.datasets[1].data.length - 2;

                    // some explanation in the tooltip
                    window.chartHospitalized.options.tooltips.callbacks.afterLabel = function(tooltipItem) {
                        if ( last == tooltipItem.index ) {
                            return ["Deze dag kan nog (behoorlijk) worden aangevuld.","Zie ook de toelichting bij 'ziekenhuisopnames'"];
                        }
                    };
                    // last bar different color
                    dataHospitalized.datasets[1].backgroundColor = [];
                    for( let i = 0; i < dataHospitalized.datasets[1].data.length; i++ ) {
                        if ( i == last ) {
                            dataHospitalized.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.3)';
                        } else {
                            dataHospitalized.datasets[1].backgroundColor[ i ] = 'rgba(54,156,210,0.5)';
                        }
                    }
                } else {
                    dataHospitalized.datasets[1].backgroundColor = 'rgba(54,156,210,0.5)';
                    window.chartHospitalized.options.tooltips.callbacks.afterLabel = Chart.defaults.global.tooltips.callbacks.afterLabel;
                }
                // console.log( city.data );
                if ( 'province' === city.data.type.region ) {
                    window.chartHospitalized.options.title.text = 'Nieuw gemelde ziekenhuisopnames per dag in ' + city.data.province.pv_name;
                }
                if ( 'country' === city.data.type.region ) {
                    window.chartHospitalized.options.title.text = 'Nieuw gemelde ziekenhuisopnames per dag in Nederland';
                }
                break;






                case 'infectionRadarPositiveTests':
                    const labelAverage1 = '7-daags gemiddelde';
                    infectionRadarPositiveTests.data = Object.assign( {}, data );

                    if( 'province' == infectionRadarPositiveTests.data.city ) {
                        infectionRadarPositiveTestsView = false;
                        CL.display.slideUpDown( containerInfectionRadarPositiveTests, 'hide', 400, 100, containerWasteWater ) ;
                    } else { 
                            infectionRadarPositiveTestsView = true;
                            CL.display.slideUpDown( containerInfectionRadarPositiveTests, 'show', 400, 100, containerWasteWater ) ;
                    }

                        
                    if ( infectionRadarPositiveTests.data && infectionRadarPositiveTests.data.chartInfectionRadarPostiveTestsData
                        && infectionRadarPositiveTests.data.chartInfectionRadarPostiveTestsData.length > 0 ) {


                        const mapped = mapInfectionRadarPositiveTestsData( chartLabels, infectionRadarPositiveTests.data );   // map the available waste water data-points to the days of the chart labels
                        dataInfectionRadarPositiveTests.labels = chartLabels;
                        dataInfectionRadarPositiveTests.datasets[1].data =mapped.data;
                        dataInfectionRadarPositiveTests.datasets[1].flags =mapped.flags;

                        // real, not interpolated, data bar different color
                        dataInfectionRadarPositiveTests.datasets[1].backgroundColor = [];
                        for( let i = 0; i < dataInfectionRadarPositiveTests.datasets[1].data.length; i++ ) {
                            if ( mapped.flags[i] ) {
                                dataInfectionRadarPositiveTests.datasets[1].backgroundColor[ i ] = 'rgba(0,58,105,0.5)';
                            } else {
                                dataInfectionRadarPositiveTests.datasets[1].backgroundColor[ i ] = 'rgba(77,125,165,0.6)';
                            }
                        }

                        
                        //dataInfectionRadarPositiveTests.datasets[0].data =mappedData;
                        dataInfectionRadarPositiveTests.datasets[0].data = calculateMovingAverage( dataInfectionRadarPositiveTests.datasets[1].data )
                        .slice( 0,chartLabels.length - 1 );

                        //dataInfectionRadarPositiveTests.datasets[1].backgroundColor = 'rgba(54,156,210,0.5)';
                        window.chartInfectionRadarPositiveTests.options.tooltips.callbacks.afterLabel = Chart.defaults.global.tooltips.callbacks.afterLabel;
                        //window.chartInfectionRadarPositiveTests.title.text = 'Percentage nieuw gemelde positieve tests per dag in Nederland';
                        window.chartInfectionRadarPositiveTests.options.title.text = 'Percentage positieve testen Nederland';
                    }
                    break;
        }

       let values;
        if ( zoom ) {
            values= {
                min: chartLabels.length - zoomDays,
                max: chartLabels.length
            };
        } else {
            let scale = allCharts[1].scales['x-axis-0'];
            values= {
                min: scale.options.ticks.minIndex,
                max: scale.options.ticks.maxIndex
            };
        }
        syncAllCharts( values, true, 400 );
        redrawZoomSymbols(); // redraw the zoom-symbols on the charts

        if ( false ) { //city.data.country !== true && ! city.data.validHospitalData && city.data.city != 'Vlieland' ) {
            document.getElementById('canvas-hospitalized-text').innerHTML = 'Deze data moet nog worden geüpdatet.';
            // document.getElementById('canvas-hospitalized-text').innerHTML = 'Vanwege de gemeentelijke herindeling worden<br>deze gegevens later vandaag geüpdatet.';
        } else {
            document.getElementById('canvas-hospitalized-text').innerHTML = '';
        }
    }

 

    /**
     * Set all charts to Nederland
     */
    charts.resetCharts = function( event ) {
        if (event && event.cancelable) {
            event.preventDefault();
        }
        CL.dataUtils.ajaxRequest( 'get','location','Nederland' );
        CL.dataUtils.ajaxRequest( 'get','plant','Nederland' );
        CL.dataUtils.ajaxRequest( 'get', 'infectionRadar', 'positiveTests' );
        /*
        if ( wastewater.data ) {
            setTimeout( function() { CL.dataUtils.ajaxRequest( 'get','plant','Nederland' ); }, 700 );
        }
        */
    };

  
    /**
     * Map the available waste water data-points to the days of the chart labels
     * for Nederland:
     * If the value == NULL we have to change it to NaN for the graph
     * This function had a more complex task but could now be much simpler
     * for RWZI location:
     * In general,only one or two measurements a week is available.
     * We would like to use the same labels as the other charts,which have daily values.
     * So we need to map the waste water data-points to all the days in the chart labels.
     */
    function mapWasteWaterData( labels,data ) {
        let mappedData = [];
        if(  'country' === data.type.region ) { // data.plant.code == 0 ) {   // Nederland
            let i = 0;
            labels.forEach( function( item ) {
                if( data.chartWasteWaterLabels[ i ] && item == data.chartWasteWaterLabels[ i ] ) {
                    if ( data.chartWasteWaterData[ i ]  === null ) {
                        mappedData.push( NaN );
                    } else {
                        mappedData.push( Math.round( data.chartWasteWaterData[ i ] )  );
                    }
                    i++;
                } else {
                    mappedData.push( NaN );
                }
            });
        } else if( 'province' === data.type.region ) { // provincie
            let i = 0;
            labels.forEach( function( item ) {
                if( data.chartWasteWaterLabels[ i ] && item == data.chartWasteWaterLabels[ i ] ) {
                    if ( data.chartWasteWaterData[ i ]  === null ) {
                        mappedData.push( NaN );
                    } else {
                        mappedData.push( Math.round( data.chartWasteWaterData[ i ] )  );
                    }
                    i++;
                } else {
                    mappedData.push( NaN );
                }
            });
        } else {    // lokale rwzi
            let i = 0;
            labels.forEach( function( item ) {
                if( data.chartWasteWaterLabels[ i ] && item == data.chartWasteWaterLabels[ i ] ) {
                    if ( data.chartWasteWaterData[ i ]  === null ) {
                        mappedData.push( NaN );
                    } else {
                        mappedData.push( Math.round( data.chartWasteWaterData[ i ] )  );
                    }
                    i++;
                } else {
                    mappedData.push( NaN );
                }
            });
        }
        return mappedData;
    }
    

    /**
     * Map the available infection radar positive tests data-points to the days of the chart labels
     * for Nederland:
     * If there is no value or the value == NULL we have to change it to NaN for the graph
     */
    function mapInfectionRadarPositiveTestsData( labels, data ) {
        let mappedData = [];
        let mappedFlags = [];
        let i = 0;

        labels.forEach( function( item ) {
            if( data.chartInfectionRadarPostiveTestsLabels[ i ] ) {
                if( null == data.chartInfectionRadarPostiveTestsData[ i ] ) {
                    mappedData.push( null );
                    mappedFlags.push( false );
                } else {
                    mappedData.push( data.chartInfectionRadarPostiveTestsData[ i ]  );
                    // LET OP: We draaien de flag om!!!!!!!! Dat sluit beter aan voor de grafiek
                    mappedFlags.push( ! data.chartInfectionRadarPostiveTestsDataFlags[ i ] );
                }
                i++;
            } else {
                mappedData.push( null );
                mappedFlags.push( false );
            }
        });

        return { data: mappedData, flags: mappedFlags };
    }









    /**
     * Calculate moving average of a dataset.
     * Default is a moving average over 7 days.
     * @param { array } dataset
     * @param { int } days
     */
    function calculateMovingAverage( dataset, days = 7 ) {
        let avg = new Array( dataset.length );
        let i,j;
        for ( i = dataset.length - 1; i > days - 1 ; i-- ){
            if( null === dataset[i] ) {
                avg[i] = 'NaN';
            } else {
                let sum = 0;
                for ( j = 0; j < days; j++ ) {
                    sum += dataset[i-j];
                }
                //  round to two decimals creates smoother graph
                avg[i] = Math.round( 100 * sum/days ) / 100;
            }
        }
        return avg;
    }



    /**
     * 
     * Notice: On sundays the hospital intake is only 0.75 of the average!
     * 
     * @param {*} sourceArray 
     */
    function getDataInterpolated( sourceArray ) {
        let dataset = deepCopy( sourceArray );
        let start,end;
        let notNull = true;
        let foundFirstValueEntry = false;

        for( let i = dataset.length -1; i > 700; i-- ) {
            if( foundFirstValueEntry || null != dataset[i] ) {
                foundFirstValueEntry = true;    // Soms is de meest recente waarde in het bestand null, omdat de lcps cijfers van gisteren vandaag pas geupdatet zijn.
                if( null == dataset [i] ) { 
                    notNull = false; // dit zijn de dagen die geïnterpoleerd moeten worden, dus we moeten door naar de eerstevolgende dag met een waarde.
                } else {
                    if( notNull ) {
                        end = i;
                    } else {
                        notNull = true;
                        start = i;

                        let startValue = dataset[ start ];
                        let endValue = dataset[ end ];
                        let interval = end - start; // - 1;

                        for( let offset = 1; offset < interval + 1; offset++ ){
                            dataset [ end - offset ] = 
                                ( ( ( ( startValue - endValue ) / interval ) * offset ) + endValue );
                                // simplified correction for the sunday hospital intake: only .75 of daily average 
                                if( 1 == offset ) dataset [ end - offset ] *= 0.75;
                        }
                    }
                }
            }
        }
        return dataset ;
    }



    /**
     * 
     * @param {*} sourceVariable 
     * @param {*} type 
     */
    function deepCopy( sourceVariable, type = '' ) {
        if( undefined === sourceVariable ) {
            console.log( 'deepCopy undefined' );
            return undefined;
        }
        return JSON.parse(JSON.stringify( sourceVariable ) )  ;
    }




}( window.CL.charts = window.CL.charts || {}, window.CL ) );
/** Corona Lokaal Charts Namespace / Namespace: CLchart */





/**
 * Corona Lokaal Maps Namespace for all maps
 * Namespace: CL.maps
 */
( function ( maps ) {

    // Some Default Config for the Maps
    const legend= {
        //enabled:false,
        floating:false,
        backgroundColor:'#ffffff',
        verticalAlign:'bottom',
        //margin: '4em',
        borderWidth:0,
        alignColumns:true,
        valueDecimals:0,
        itemStyle:{
            fontSize:'80%',
            fontWeight:'normal',
            fontStyle:'none',
            color:'gray',
            textDecoration:'none'
        },
        itemMarginTop:0,
        itemMarginBottom:0,
        margin:10,
        padding:0,
        width:'80%',
        align: 'left',
        x: 40,
        y: ( window.innerWidth < 600 ) ? 0 : 12,
    };
    const mapNavigation = {
            enabled:true,
            buttonOptions:{
                verticalAlign:'bottom'
            },
            enableDoubleClickZoom:false
    };
    const plotOptions = {
        series:{
            states:{
                inactive:{
                    opacity:1
                }
            }
        }
    };
    
    // create the waste water bubbles in the map
    const seriesBubbles = {
        type:'mapbubble',
        name:'virusdeeltjes in rioolwater',
        id:'waste-water-bubbles',
        dataLabels:{
            enabled:false
        },
        events:{
            click:function( event ) {
                CL.dataUtils.ajaxRequest( 'get','plant',event.point.name );
            }
        },
        data:[],
        minSize:'2%',
        maxSize:'2%',
        tooltip:{
            distance:70,
            style:{
                color:'#666',
                lineHeight:'20px'
            },
            pointFormatter:function(){
                let s = '<span style="font-weight:bold;font-size:14px;"> ' + this.name
                    + '</span><br><span style="color:#666;font-size:12px;">• '+ this.rna
                    + ' coronavirusdeeltjes per 100.000 inwoners, meting van '+ this.date + '</span><br>';
                return s;
            }
        },
        marker:{
            lineColor:'#666',
            lineWidth:1,
            states:{
                hover:{
                    lineWidth:2,
                    fillColor:'#369cd2',
                    lineColor:'#369cd2'
                }
            }
        }
    };

    let mapContainerPositiveTests,mapContainerVaccinatedPerCity,mapContainerHospitalizedPerCity,mapContainerWasteWaterPerProvince;
    let zoomInBubbles = 100;
    let titleText;

    // DOM content is loaded so select elements and add event listeners
    maps.initDOMLoaded = function() {
        mapContainerPositiveTests = document.getElementById('map-container-positive-tests');
        mapContainerVaccinatedPerCity = document.getElementById('map-container-vaccinated-per-city');
        mapContainerHospitalizedPerCity = document.getElementById('map-container-hospitalized-per-city');
        mapContainerWasteWaterPerProvince = document.getElementById('map-container-waste-water-per-province');

        document.getElementById('chkbx-vaccinated-per-city').addEventListener( 'click',function () {
            mapOptionsClicked( 'vaccinated-per-city' );
        });
        document.getElementById('chkbx-positive-tests').addEventListener( 'click',function () {
            mapOptionsClicked( 'positive-tests' );
        });
        document.getElementById('chkbx-hospitalized-per-city').addEventListener( 'click',function () {
            mapOptionsClicked( 'hospitalized-per-city' );
        });
        document.getElementById('chkbx-waste-water').addEventListener( 'click',function () {
            mapOptionsClicked( 'waste-water' );
        });
        document.getElementById('chkbx-waste-water-bubbles').addEventListener( 'click',function () {
            mapOptionsClicked( 'waste-water-bubbles' );
        });


        // add vaccinatiegraad per wijk on click button.
        const buttonPerNeighbourhood = document.getElementById('button-vaccinatiegraad-per-wijk');
        if( undefined !== buttonPerNeighbourhood && null !==  buttonPerNeighbourhood) {
            buttonPerNeighbourhood.addEventListener('click', function( event ) {
                maps.toggleVaccinesPerNeighbourhood( true, event );
            });
        }


    };


    /**
     * init onload
     */
    maps.initWindowLoaded = function() {
        // checkboxes and titles
        window.chckbxWasteWater = document.getElementById( 'chkbx-waste-water' );
        window.chckbxWasteWaterBubbles = document.getElementById( 'chkbx-waste-water-bubbles' );
        window.chckbxPositiveTests = document.getElementById( 'chkbx-positive-tests' );
        window.chckbxVaccinatedPerCity = document.getElementById( 'chkbx-vaccinated-per-city' );
        window.chckbxHospitalizedPerCity = document.getElementById( 'chkbx-hospitalized-per-city' );
        let titleText = ( window.chckbxPositiveTests.checked ) ? 'Positieve testen afgelopen 7 dagen per 100.000 inwoners' :'';
        if ( window.chckbxPositiveTests.checked && window.chckbxWasteWater.checked ) titleText += ' /<br>';
        titleText += ( window.chckbxWasteWater.checked ) ? 'Virusdeeltjes in het rioolwater per 100.000 inwoners' :'';


        titleText = 'Rioolwaterwaarden per provincie';
        // create maps
        maps.createMapWasteWaterProvince();
        // maps.createMapPositiveTests( titleText ); // not used anymore
        setTimeout( function() { // not everything at once,so delay to ensure smooth animations
            // maps.createMapVaccinatedPerCity(); // not used anymore
            // maps.createMapHospitalizedPerCity(); // not used anymore
            // maps.createMapVaccinatedPerNeigbourhood(); // not used anymore
        },1500 );

        // create the waste water bubbles if checked
        seriesBubbles.data = plantLocations;
        if( window.chckbxWasteWater.checked ) {
            titleText += '<br>en per rioolwaterzuivering';
            // window.mapNLWasteWater.addSeries( seriesBubbles,true);
        }

        setTimeout( function() { // not everything at once,so delay to ensure smooth animations
            document.getElementById('numberWasteWaterLocations').innerHTML = plantLocations.length;
        },2000 );


        /*
        var cont = document.getElementById('map-container-positive-tests');
        var size = document.getElementById('map-container-positive-tests').getClientRects()[0];
        console.log( size[0] );
        document.getElementById('feedback').innerHTML = 'h: ' + size.height + ', w: ' + size.width ;
        cont.style.border = '1px solid #000';
*/


    };


/*
    maps.moveLegend = function( type ) {
        return;
        switch( type ) {
            case 'up': 
                document.querySelectorAll('.highcharts-legend').forEach(function(item) {
                    var xforms = item.transform.baseVal; 
                        // Find the right transform type ( SVGTransform.SVG_TRANSFORM_TRANSLATE )
                    for (var i=0; i<xforms.numberOfItems; ++i){
                        // Get this part as an SVGTransform
                        var xform = xforms.getItem(i);
                        if( SVGTransform.SVG_TRANSFORM_TRANSLATE == xform.type ) { // https://www.w3.org/TR/SVG2/coords.html
                            let x = xform.matrix.e;
                            let y = xform.matrix.f;
                            y -= 50
                            console.log( x + ', ' + y ); // https://www.w3.org/TR/SVG/coords.html#InterfaceSVGTransform
                            xform.setTranslate(x, y);
                        }
                    }
                });
            break;
        }  
            
    };
*/




    /**
     * Blink the area of the selected city on the map
     * @param {string} cityName
     */
    let blinking = {};
    const blinkColor = '#369cd2';
    maps.blinkCity = function( cityName ) {

        // we have no data per city any longer, so return immediately
        return;

        if ( cityName == 'Nederland' ) return;
        blinking.index = window.mapNL.series[0].points.findIndex((p) => p.cityname === cityName );
        if ( undefined === blinking.index ) return;
        blinking.baseColor =  window.mapNL.series[0].points[ blinking.index ].color;
        let blink = false;
        blinking.intervalId = setInterval( function(){
            window.mapNL.series[0].points[ blinking.index ].update({
                color : (blink) ? blinking.baseColor : blinkColor,
            });
            blink = ! blink;
        },300);
        setTimeout( function(){ maps.clearblinkCity(); },4000 );
    };


    /**
     * Clear the blinking started by blinkingCity();
     */
    maps.clearblinkCity = function() {
        if ( undefined === blinking.intervalId ) return;
        clearInterval( blinking.intervalId );
        window.mapNL.series[0].points[ blinking.index ].update({
            color :blinking.baseColor
        });
    };


    /**
     * Is the waste water checkbox checked?
     */
    maps.isWasteWaterVisible = function() {
        return window.chckbxWasteWater.checked;
    };


    /**
     * controlthe waste water bubbles from outside.
     * Used by close button on waste water chart.
     * @param {enum} type 
     */
    maps.hide = function( type ) {
        switch( type ) {
            case 'waste-water':
                window.chckbxWasteWater.checked = false;
                mapOptionsClicked( type );
            break;
        }
    }


    /**
     * interactivity of the checkboxes beneath the map
     */
    function mapOptionsClicked( type ) {
        let positiveTestsChecked = window.chckbxPositiveTests.checked;
        let wasteWaterChecked = window.chckbxWasteWater.checked;
        let wasteWaterBubblesChecked = window.chckbxWasteWaterBubbles.checked;
        let vaxxedChecked = window.chckbxVaccinatedPerCity.checked;
        let hospitalizedChecked = window.chckbxHospitalizedPerCity.checked;

        mapContainerVaccinatedPerCity.classList.add( 'hide' );
        mapContainerHospitalizedPerCity.classList.add( 'hide' );
        mapContainerPositiveTests.classList.add( 'hide' );
        mapContainerWasteWaterPerProvince.classList.add( 'hide' );

        window.chckbxPositiveTests.checked = false;
        window.chckbxVaccinatedPerCity.checked = false;
        window.chckbxHospitalizedPerCity.checked = false;
        window.chckbxWasteWater.checked  = false;

        if( ! wasteWaterChecked && ! hospitalizedChecked ) {
            wasteWaterChecked = true;
            type = 'waste-water';
        }

        switch( type ) {
            case 'waste-water-bubbles':
                toggleBubbles( wasteWaterBubblesChecked );
                wasteWaterChecked = true;
//                if( wasteWaterBubblesChecked ) wasteWaterChecked = true;
            case 'waste-water' :
            if ( wasteWaterChecked ) {
                mapContainerWasteWaterPerProvince.classList.remove( 'hide' );
                window.chckbxWasteWater.checked = true;
            } else {
                if( window.mapNL.series[0].points[ 0 ].citycode == undefined ) {
                    window.mapNL.series[0].update({ data:mapData });
                }
                mapContainerPositiveTests.classList.remove( 'hide' );
                window.chckbxPositiveTests.checked = true;
            }
            let titleText = 'Rioolwaterwaarden per provincie';
            if( wasteWaterBubblesChecked ) titleText += '<br>en per rioolwaterzuivering';
            window.mapNLWasteWater.title.update({ text:titleText });
            window.mapNLWasteWater.redraw();




                //mapContainerPositiveTests.classList.remove( 'hide' );
                //window.chckbxPositiveTests.checked = true;
            break;

            case 'positive-tests' :
                if ( positiveTestsChecked ) { 
                    if( window.mapNL.series[0].points[ 0 ].citycode == undefined ) {
                        window.mapNL.series[0].update({ data:mapData });
                    }
                    window.chckbxPositiveTests.checked = true;
                }
                else { 
                    window.mapNL.series[0].update({ data:[] });
                }
                mapContainerPositiveTests.classList.remove( 'hide' );
                break;
            case 'vaccinated-per-city' :
                if ( vaxxedChecked ) {
                    mapContainerVaccinatedPerCity.classList.remove( 'hide' );
                    window.chckbxVaccinatedPerCity.checked = true;
                } else {
                    if( window.mapNL.series[0].points[ 0 ].citycode == undefined ) {
                        window.mapNL.series[0].update({ data:mapData });
                    }
                    mapContainerPositiveTests.classList.remove( 'hide' );
                    window.chckbxPositiveTests.checked = true;
                }
                return;
            case 'hospitalized-per-city' : 
                if ( hospitalizedChecked ) {
                    mapContainerHospitalizedPerCity.classList.remove( 'hide' );
                    window.chckbxHospitalizedPerCity.checked = true;
                } else {
                    if( window.mapNL.series[0].points[ 0 ].citycode == undefined ) {
                        window.mapNL.series[0].update({ data:mapData });
                    }
                    mapContainerPositiveTests.classList.remove( 'hide' );
                    window.chckbxPositiveTests.checked = true;
                }
                return;
        }
        /*
        let titleText = ( positiveTestsChecked ) ? 'Positieve testen afgelopen 7 dagen per 100.000 inwoners' :'';
        if ( positiveTestsChecked && wasteWaterChecked ) titleText += ' /<br>';
        titleText += ( wasteWaterChecked ) ? 'Virusdeeltjes in het rioolwater per 100.000 inwoners' :'';
        window.mapNL.title.update({ text:titleText });
        window.mapNL.redraw();
        */
    }


    /**
     * Add or remove waste water bubbles.
     * use param 'toggle' to force bubbles visible (true) or hidden (false)
     */
    function toggleBubbles( toggle = null ) {
        let seriesBubblesToggle = ( window.mapNLWasteWater.series.length == 1 ) ? true :false;
        if( toggle === true ) seriesBubblesToggle = true;
        else if( toggle === false ) seriesBubblesToggle = false;

        if ( seriesBubblesToggle && window.mapNLWasteWater.series.length == 1 ) {
           //CL.display.scrollToTop();
            setTimeout( function() {
                window.mapNLWasteWater.addSeries( seriesBubbles,true );
            },1 );
            //CL.charts.toggleWasteWater( true );
        }
        if ( ! seriesBubblesToggle && window.mapNLWasteWater.series.length == 2 ) {
            window.mapNLWasteWater.get('waste-water-bubbles').remove();
           // CL.charts.toggleWasteWater( false );
        }
        CL.main.redrawTitles();
    }


    /**
     * Zoom bubbles:Enlarge the bubbles if user zooms in.
     * Notice:zoom factor is other way around. Zoom in means smaller values. ;-)
     */
    function zoomBubbles( currentZoomStatus = 100 ) {
        if ( window.mapNLWasteWater.series.length == 1 ) return true;  // no active bubbles
        
        if ( currentZoomStatus < 25  && 25 != zoomInBubbles ) {
            window.mapNLWasteWater.series[1].update({
                minSize:'6%',
                maxSize:'6%'
            }, true );
            zoomInBubbles = 25;
        } else if ( currentZoomStatus > 25 && currentZoomStatus < 50  && 50 != zoomInBubbles ) {
            window.mapNLWasteWater.series[1].update({
                minSize:'4%',
                maxSize:'4%'
            }, true );
            zoomInBubbles = 50;
        } else if ( currentZoomStatus > 50 && 100 != zoomInBubbles ) {
            window.mapNLWasteWater.series[1].update({
                minSize:'2%',
                maxSize:'2%',
            },true );
            zoomInBubbles = 100;
        }
        return;
    }























    /******* THE MAPS ******* THE MAPS ******* THE MAPS ******* THE MAPS *******/



   /** 
     * create the map positive tests
     */

    maps.createMapHospitalizedPerCity = function() {
        console.log( 'mapDataHospitalizedPerProvince' );
        console.log( mapDataHospitalizedPerProvince );
        if ( ! mapDataHospitalizedPerProvince ) return;
        const factor = 1;
        window.mapNLHospitalized = Highcharts.mapChart('map-container-hospitalized-per-city',{
            chart:{ map:'provincie' },
            title:{
                text:'Ziekenhuisopnames per provincie',
                style:{ fontFamily:'arial',fontSize:'12px',color:'#666',fontWeight:'bold' }
            },
            credits:{
                text:today + ' corona-lokaal.nl',
                href:''
            },
            legend,
            mapNavigation,
            xAxis:{
                events:{
                    afterSetExtremes:function(event){
                        if( "setExtremes" == event.type ) {
                            let currentZoomStatus = Math.round( 100 *  ( event.max - event.min) / ( event.dataMax - event.dataMin)  );
                                zoomBubbles( currentZoomStatus );
                        }
                    }
                }
            },
            colors:['#ffffff','#fdf5c8','#fed066','#e58d35','#cc3d19','#a11a14','#580000','#000000'],
            colorAxis:{
            dataClassColor:'category',
            dataClasses:[{ to:5*factor },
                { from:5*factor,to:20*factor },
                { from:20*factor,to:40*factor },
                { from:40*factor,to:60*factor },
                { from:60*factor,to:80*factor },
                { from:80*factor,to:100*factor},
                { from:100*factor,to:120*factor},
                { from:120*factor }],
/*
                dataClasses:[{ to:5*factor },
                    { from:5*factor,to:10*factor },
                    { from:10*factor,to:20*factor },
                    { from:20*factor,to:30*factor },
                    { from:30*factor,to:40*factor },
                    { from:40*factor,to:50*factor },
                    { from:50*factor,to:75*factor},
                    { from:75*factor }],
*/


            // max:1000,min:0,startOnTick:false,endOnTick:false
            },
            plotOptions,
            series:[{
                data: mapDataHospitalizedPerProvince,
                // data:[],
                joinBy:['statcode','pv_code'],
                name:'Ziekenhuisopnames',
                id:'hospital-admission-per-province-map',
                borderColor:'#bbb',
                borderWidth:0.4,
                dataLabels:{ enabled:false },
                states:{
                    hover:{ color:'#369cd2' }
                },
                events:{
                    click:function( event ) {
                        if ( ! window.CL.touchscreen ) {
                            // domContentStart = performance.now();
                            CL.dataUtils.ajaxRequest( 'get', 'hospitalized', event.point.pv_code );
                            CL.dataUtils.ajaxRequest( 'get', 'plant', event.point.pv_code );
                        } else { // create a 'double touch' action for touchscreens ( a single 'touch' equals mouse hover on PC's )
                            if ( window.clicked == event.point.pv_code ) {
                                clearTimeout( window.clickTimer );
                                // domContentStart = performance.now();
                                CL.dataUtils.ajaxRequest( 'get', 'hospitalized', event.point.pv_code );
                                CL.dataUtils.ajaxRequest( 'get', 'plant', event.point.pv_code );
                                window.clicked = false;
                            } else {
                                window.clicked = event.point.pv_code;
                                window.clickTimer = setTimeout( function(){
                                    window.clicked = false;
                                },500 );
                            }
                        }
                    }
                },
                tooltip:{
                    distance:70,
                    style:{ color:'#666',lineHeight:'20px' },
                    pointFormatter:function(){
                        let s =  '<span style="font-weight:bold;font-size:14px;"> ' + this.pv_name + '</span>'
                        + '<br><span style="color:#666;font-size:12px;">• '+ this.daily_intake
                        + ' gemiddeld over zeven dagen</span>'
                        + '<br><span style="color:#666;font-size:12px;">• Dat is '+ this.value
                        + ' per miljoen inwoners.</span><br>'
                        if ( window.CL.touchscreen ) {
                            s += '<span style="color:#fff;font-size:10px;">• </span><span style="color:#666;font-size:10px;">dubbel klik de provincie om de grafieken van ' + this.cityname  + ' te bekijken</span><br>';
                        } else {
                            s += '<span style="color:#fff;font-size:10px;">• </span><span style="color:#666;font-size:10px;">(klik voor de grafieken)</span><br>';
                        }
                        return s;
                    }
                }

            }]   // series
        }); 
    };    // create the map positive tests










   /** 
     * create the map positive tests
     */
    maps.createMapWasteWaterProvince = function() {
        const factor = 1;
        window.mapNLWasteWater = Highcharts.mapChart('map-container-waste-water-per-province',{
            chart:{ map:'provincie' },
            title:{
                text: 'Rioolwaterwaarden per provincie',
                style:{ fontFamily:'arial',fontSize:'12px',color:'#666',fontWeight:'bold' }
            },
            credits:{
                text:today + ' corona-lokaal.nl',
                href:''
            },
            legend,
            mapNavigation,
            xAxis:{
                events:{
                    afterSetExtremes:function(event){
                        if( "setExtremes" == event.type ) {
                            let currentZoomStatus = Math.round( 100 *  ( event.max - event.min) / ( event.dataMax - event.dataMin)  );
                                zoomBubbles( currentZoomStatus );
                        }
                    }
                }
            },
            colors:['#ffffff','#fdf5c8','#fed066','#e58d35','#cc3d19','#a11a14','#580000','#000000'],
            colorAxis:{
            dataClassColor:'category',


            dataClasses:[{ to:100*factor },
                { from:100*factor,to:400*factor },
                { from:400*factor,to:800*factor },
                { from:800*factor,to:1300*factor },
                { from:1300*factor,to:1800*factor },
                { from:1800*factor,to:2400*factor},
                { from:2400*factor,to:3000*factor},
                { from:3000*factor }],


/*
            dataClasses:[{ to:1 },
                { from:1,to:6*factor },
                { from:6*factor,to:15*factor },
                { from:15*factor,to:25*factor },
                { from:25*factor,to:50*factor },
                { from:50*factor,to:75*factor },
                { from:75*factor,to:100*factor},
                { from:100*factor }],
*/
            // max:1000,min:0,startOnTick:false,endOnTick:false
            },
            plotOptions,
            series:[{
                data: mapDataWasteWaterPerProvince,
                // data:[],
                joinBy:['statcode','pv_code'],
                name:'Virusniveau rioolwater',
                id:'waste-water-per-province-map',
                borderColor:'#bbb',
                borderWidth:0.4,
                dataLabels:{ enabled:false },
                states:{
                    hover:{ color:'#369cd2' }
                },
                events:{
                    click:function( event ) {
                        if ( ! window.CL.touchscreen ) {
                            // domContentStart = performance.now();
                            CL.dataUtils.ajaxRequest( 'get', 'hospitalized', event.point.pv_code );
                            CL.dataUtils.ajaxRequest( 'get', 'plant', event.point.pv_code );
                            CL.dataUtils.ajaxRequest( 'get', 'infectionRadar', event.point.pv_code );
                        } else { // create a 'double touch' action for touchscreens ( a single 'touch' equals mouse hover on PC's )
                            if ( window.clicked == event.point.pv_code ) {
                                clearTimeout( window.clickTimer );
                                // domContentStart = performance.now();
                                CL.dataUtils.ajaxRequest( 'get', 'hospitalized', event.point.pv_code );
                                CL.dataUtils.ajaxRequest( 'get', 'plant', event.point.pv_code );
                                CL.dataUtils.ajaxRequest( 'get', 'infectionRadar', event.point.pv_code );
                                window.clicked = false;
                            } else {
                                window.clicked = event.point.pv_code;
                                window.clickTimer = setTimeout( function(){
                                    window.clicked = false;
                                },500 );
                            }
                        }
                    }
                },
                tooltip:{
                    distance:70,
                    style:{ color:'#666',lineHeight:'20px' },
                    pointFormatter:function(){
                        let s =  '<span style="font-weight:bold;font-size:14px;"> ' + this.pv_name
                        + '</span><br><span style="color:#666;font-size:12px;">• '+ this.value
                        + '  gemiddeld over zeven dagen'
                        + '</span><br><span style="color:#fff;font-size:10px;">• </span><span style="color:#666;font-size:10px;">(betrouwbaarheid ' + this.reliability + '%)</span><br>';
                        if ( window.CL.touchscreen ) {
                            s += '<span style="color:#fff;font-size:10px;">• </span><span style="color:#666;font-size:10px;">dubbel klik de provincie om de grafieken van ' + this.pv_name  + ' te bekijken</span><br>';
                        } else {
                            s += '<span style="color:#fff;font-size:10px;">• </span><span style="color:#666;font-size:10px;">(klik voor de grafieken)</span><br>';
                        }
                        return s;
                    }
                }

            }]   // series
        }); 
    };    // create the map positive tests








}( window.CL.maps = window.CL.maps || {} ) );
/** Corona Lokaal Maps Namespace / Namespace: CLmap */






/**
 * Corona Lokaal Texts Namespace for all extra texts
 * Namespace: CL.texts
 */

( function ( texts, CL ) {
    let qenaTextContainer;

    /**
     * DOM content is loaded so select elements and add event listeners
     */
    texts.initDOMLoaded = function() {
        qenaTextContainer = document.getElementById('qena-container');
       // canvasWasteWater = document.getElementById('canvas-waste-water');
       // canvasReported = document.getElementById('canvas-reported');

        // allCanvasses = [ canvasWasteWater, canvasHospitalized, canvasDeceased ];

        //zoomSymbolsList = document.querySelectorAll('.zoom-container .zoom');  // array of magnifier symbols
       // chartSettingsZoomDays = document.getElementById('chart-setting-zoom-days' );  // input setting zoom days
     

        // add chart settings box on click settings icon.
        //document.getElementById('submit-chart-settings').addEventListener( 'click',function () {
        //    setZoomDays( chartSettingsZoomDays.value );
        //});

        // add zoom action to charts.
        document.querySelectorAll( '.click-text-container' ).forEach( item => {
            item.addEventListener('click',function() {
                CL.texts.getTextData( item );
            });
        });

    }; // DOMContentLoaded


    /**
     * Event window loaded: All elements for the webpage are loaded

     */
    texts.initWindowLoaded = function() {


    }; // windowLoaded


    
    /**
     * Get the requested text by Ajax-request.
     */
    texts.getTextData = function( item ) {
        let textId = item.id.replace( 'question', 'answer' ); 
        let currentAnswer = document.getElementById( textId );
        if( ! currentAnswer.classList.contains( 'hide' ) ) {
            showTextElement( '' );
            return;
        }

        // check if text already there.
        if( currentAnswer.textContent.length > 100 ) {
            showTextElement( currentAnswer );
            return;
        }

        // get text
        CL.dataUtils.ajaxRequest( 'get', 'text', item.id );
    }


    /**
     * 
     * @param { html-element } element to be shown. If empty all elements are/stay hidden
     */
    function showTextElement( element ) {
        // first, close all open answers
        document.querySelectorAll( '.qena-answer' ).forEach( item => item.classList.add( 'hide' ));
        document.querySelectorAll( '.qena-question .fa-solid' ).forEach( item => item.innerHTML = '&#xf13a;' );
        if ( element ) {
            element.classList.remove( 'hide' ); // show only this specific answer
            let questionId = element.id.replace( 'answer', 'question' ); 
            let question = document.querySelector( '#' + questionId + ' .fa-solid' );
            if( question ) question.innerHTML = '&#xf139;';
        }

    }


    /**
     * Set the data received by an ajax-request to the charts.
     * @param {enum} type 
     * @param {array-object} data 
     */
    texts.setTextData = function( data ) {
        let currentAnswer = document.getElementById( data.textId );
        currentAnswer.innerHTML = data.textData;
            showTextElement( currentAnswer );
    }

 
    /**
     * Get the requested text by Ajax-request.
     */
    texts.enlargeImage = function( image ) {
        let popupImage = document.querySelector( '#popupEnlargedImage .popup-contents');
        popupImage.innerHTML = "<img src='" + image.src + "' alt=''>";
        window.CL.display.popupToggle( 'popupEnlargedImage', 0, 0, true );
    }



}( window.CL.texts = window.CL.texts || {}, window.CL ) );
/** Corona Lokaal Texts Namespace */




var domContentStart;

( function ( main, CL ) {

    // some settings for navigating the map using touchscreens
    CL.touchscreen = false;
    window.clicked = false;
    window.clickTimer = 0;

    let twitter,facebook,linkedin,email,selectLocation,title,titleH1,
                        inputLocationListContainer,inputLocationList;
    let goToTop = true;


    /**
     * DOM content is loaded so select elements and add event listeners
     */
    window.addEventListener('DOMContentLoaded',() => {
        twitter = document.getElementById('twitter');
        facebook = document.getElementById('facebook');
        linkedin = document.getElementById('linkedin');
        email = document.getElementById('email');
        title = document.getElementById('title');
        titleH1 = document.getElementsByTagName("h1")[0];
        selectLocation = document.getElementById('location-search-input');
        
        inputLocationListContainer = document.getElementById('location-search');
        inputLocationList = inputLocationListContainer.querySelector("ul");

        cities.push({"code":0,"city":"Nederland"});
        CL.charts.initDOMLoaded();
        CL.maps.initDOMLoaded();
        CL.texts.initDOMLoaded();

        // place the waste water chart on the right position (responsive)
        goToTop = CL.display.responsivePositions();

        // add chart settings box on click settings icon.
        document.getElementById('logo-click').addEventListener( 'click',function () {
            CL.charts.resetCharts();
        });
        window.addEventListener("resize", CL.display.responsivePositions );
        // check for touchscreens
        window.addEventListener("touchstart",function onFirstTouch() {
            window.CL.touchscreen = true;
            window.removeEventListener('touchstart',onFirstTouch,false);
        });
        // add close action to all popups so they can be closed
        document.querySelectorAll( '.click-to-close' ).forEach( item => {
            item.addEventListener('click',event => {
                const targetPopup = event.target.closest( '.popup' );
                CL.display.popupToggle ( targetPopup.id );
            });
        });
        // add chart settings box on click settings icon.
        selectLocation.addEventListener('keyup', function( event ) {
            if ( 'Enter' === event.key ) return;
            createLocationList( this.value );
        });
        selectLocation.addEventListener('click', function() {
            this.select();
        });
        selectLocation.addEventListener('keydown', function( event ) {
            if ( 'Tab' === event.key ) {
                event.preventDefault();
                checkAndLoadLocation( this.value );
            }
        });
        selectLocation.addEventListener('keypress', function( event ) {
            if ( 'Enter' === event.key ) {
                checkAndLoadLocation( this.value );
            }
        });

    }); // DOM content loaded

    
    /**
     * Page is loaded: Draw the charts and the maps as soon as the page is loaded.
     */
    window.addEventListener('load',() => {
        CL.charts.initWindowLoaded();
        CL.maps.initWindowLoaded();
        CL.texts.initWindowLoaded();

        let simpelNamesArray = [
            "Groningen",
            "Friesland",
            "Drenthe",
            "Overijssel",
            "Flevoland",
            "Gelderland",
            "Utrecht",
            "Noord-Holland",
            "Zuid-Holland",
            "Zeeland",
            "Noord-Brabant",
            "Limburg"
        ];
        const simpelCodesArray = [
            "PV20",
            "PV21",
            "PV22",
            "PV23",
            "PV24",
            "PV25",
            "PV26",
            "PV27",
            "PV28",
            "PV29",
            "PV30",
            "PV31"
        ];

        let codeKey = simpelNamesArray.indexOf( loc );
        if( codeKey > -1 ) loc = simpelCodesArray[ codeKey ];
        codeKey = simpelNamesArray.indexOf( plant );
        if( codeKey > -1 ) plant = simpelCodesArray[ codeKey ];


        if( ! plant ) plant = 'Nederland';
        if( 'Nederland' == loc ) {
            CL.dataUtils.ajaxRequest( 'get', 'location', 'Nederland' );
            setTimeout( function() {
                CL.dataUtils.ajaxRequest( 'get', 'infectionRadar', 'positiveTests' );
            }, 500 );
        } else {
            CL.dataUtils.ajaxRequest( 'get', 'hospitalized', loc );
            // Infection Radar is only for whole country available, so


        }
        setTimeout( function() {
            CL.dataUtils.ajaxRequest( 'get', 'plant', plant );
        }, 250 );
    });


    /**
     * Creates a list where the given characters match the first characters of the city name
     * @param { string } chars The (first) characters of the name. 
     */
    function createLocationList( chars = '' ) {
        let list = '';
        let i = 0;
        const filter = chars.trim().toUpperCase();
        if( '' !== filter ) {
            for ( let item of cities ) {
                if ( item.city.substring( 0,filter.length ).toUpperCase() === filter ) {
                    list += '<li data-city="' + item.city + '">' + item.city + '</li>';
                    i++;
                    if ( i > 7 ) break;
                }
            }
        }
        inputLocationList.innerHTML = list;
        inputLocationList.querySelectorAll( 'li' ).forEach( item => {
            item.addEventListener('click',event => {
                CL.dataUtils.ajaxRequest( 'get','location',event.target.dataset.city );
                inputLocationList.innerHTML = '';
            });
        });
    }


    /**
     * Get the first city that matches the given input characters
     * @param { String } chars The (first) characters of the name. 
     */
    function checkAndLoadLocation( chars = '' ) {
        const filter = chars.trim().toUpperCase();
        if( '' !== filter ) {
            for ( let item of cities ) {
                if ( item.city.substring( 0,filter.length ).toUpperCase() === filter ) {
                    CL.dataUtils.ajaxRequest( 'get','location',item.city );
                    selectLocation.blur();
                    inputLocationList.innerHTML = '';
                    return;
                }
            }
        }
    }


    /**
     * Write new titles and share buttons if the chart data has changed
     */
    main.redrawTitles = function() {

        let hospitalizedLocation = CL.charts.getLocation();
        let wasteWaterLocation = CL.charts.getWastewaterLocation();

        // console.log( hospitalizedLocation);

        if( ! hospitalizedLocation.name ) hospitalizedLocation.name = 'Nederland';
        if( ! wasteWaterLocation.name ) wasteWaterLocation.name = 'Nederland';


        let tmpTitle = hospitalizedLocation.name + '/' + wasteWaterLocation.name;

        /*
        title.innerHTML = tmpTitle + " - bijgewerkt tot en met "
                        + CL.charts.getChartLabel( 'city', -1 );
                        + " op basis van cijfers RIVM.";
        selectLocation.value = locationName;
        */

        // set URL and address bar
        let url = baseUrl + 'locatie/' + hospitalizedLocation.name + '/waterzuivering/' + wasteWaterLocation.name;
        window.history.pushState('', baseTitle + tmpTitle, url );
        window.document.title = baseTitle + tmpTitle;
        titleH1.innerHTML = siteName + ' | ' + tmpTitle;

        // share buttons.
        let shareText = 'Bekijk%20de%20corona-ontwikkelingen%20in%20' + hospitalizedLocation.name
                    + '%20met%20waterzuivering%20' + wasteWaterLocation.name;

        const sourceText = '%0A%0ACorona%20Lokaal%20-%0A';
        //const urlText = encodeURIComponent( baseUrl + 'location/' + city.data.city );
        const urlText = encodeURIComponent( url );
        twitter.href = 'https://twitter.com/intent/tweet?text=' + shareText + sourceText + '&url=' + urlText;
        facebook.href = 'https://www.facebook.com/sharer/sharer.php?u=' + urlText;
        linkedin.href = 'https://www.linkedin.com/shareArticle?mini=true&title=' + shareText + '&source=' + sourceText + '&url=' + urlText;
        email.href = 'mailto:?subject='+ shareText + '&body=' + shareText + '%0A%0A' + urlText + '%0A%0A%0A';
    };


    /**
     * Handle the response from the Ajax Request
     * @param {JSON} response 
     */
    main.resolveAjaxRequest = function( action, response ) {
        // console.log( 'we are in main.resolveAjaxRequest()' );
        // console.log( response );
        // console.log( response.data );
        if ( response.success ) {
            switch ( action ) {
                case 'get' :
                    let locationName = '';
                    if ( response.data.updating ) {
                        title.innerHTML = "Een moment geduld aub. De site wordt op dit moment geüpdatet. Dit duurt enkele seconden.";
                        break;
                    }
                    if( response.data.type.category == 'text' ) {
                        CL.texts.setTextData( response.data );
                    }


                    if ( response.data.type.category == 'wastewater' ) {

/*
                        if ( goToTop ) {
                            CL.display.scrollToTop();
                        }
*/
                        CL.charts.setChartData( 'wastewater', response.data );
                        //CL.charts.redrawCharts( 'wastewater' );
                    }
                    if ( response.data.type.category == 'hospitalized' ) {
                        CL.charts.setChartData( 'hospitalized', response.data );
                    }
                    if ( response.data.type.category == 'infectionRadarPositiveTests' ) {
                        CL.charts.setChartData( 'infectionRadarPositiveTests', response.data );
                    }
                    if ( response.data.city || response.data.country ) {
                        locationName = response.data.city;
                        CL.display.scrollToTop();
                        CL.charts.setChartData( 'city', response.data );
                        //CL.charts.redrawCharts( 'city' );
                        if ( response.data.city && ! response.data.country ) {
                            CL.maps.blinkCity( locationName );
                        }
                    }
                    main.redrawTitles( locationName );
                    break;
            }
        } else {
            title.innerHTML = "Er ging iets mis. Probeer het opnieuw.";
        }
    };



 



}( window.CL.main = window.CL.main || {}, window.CL ) );



