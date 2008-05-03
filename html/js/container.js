/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

var gadgets = gadgets || {};

/**
 * @fileoverview General purpose utilities that gadgets can use.
 */

/**
 * @static
 * @class Provides general-purpose utility functions.
 * @name gadgets.util
 */

gadgets.util = function() {
  /**
   * Parses URL parameters into an object.
   * @return {Array.&lt;String&gt;} The parameters
   */
  function parseUrlParams() {
    // Get settings from url, 'hash' takes precedence over 'search' component
    // don't use document.location.hash due to browser differences.
    var query;
    var l = document.location.href;
    var queryIdx = l.indexOf("?");
    var hashIdx = l.indexOf("#");
    if (hashIdx === -1) {
      query = l.substr(queryIdx + 1);
    } else {
      // essentially replaces "#" with "&"
      query = [l.substr(queryIdx + 1, hashIdx - queryIdx - 1), "&",
               l.substr(hashIdx + 1)].join("");
    }
    return query.split("&");
  }

  var parameters = null;
  var features = {};
  var onLoadHandlers = [];

  // Maps code points to the value to replace them with.
  // If the value is "false", the character is removed entirely, otherwise
  // it will be replaced with an html entity.
  var escapeCodePoints = {
   // nul; most browsers truncate because they use c strings under the covers.
   0 : false,
   // new line
   10 : true,
   // carriage return
   13 : true,
   // double quote
   34 : true,
   // single quote
   39 : true,
   // less than
   60 : true,
   // greater than
   62 : true,
   // Backslash
   92 : true,
   // line separator
   8232 : true,
   // paragraph separator
   8233 : true
  };

  /**
   * Regular expression callback that returns strings from unicode code points.
   *
   * @param {Array} match Ignored
   * @param {String} value The codepoint value to convert
   * @return {String} The character corresponding to value.
   */
  function unescapeEntity(match, value) {
    return String.fromCharCode(value);
  }

  /**
   * Initializes feature parameters.
   */
  function init(config) {
    features = config["core.util"] || {};
  }
  if (gadgets.config) {
    gadgets.config.register("core.util", null, init);
  }

  return /** @scope gadgets.util */ {

    /**
     * Gets the URL parameters.
     *
     * @return {Object} Parameters passed into the query string
     * @member gadgets.util
     * @private Implementation detail.
     */
    getUrlParameters : function () {
      if (parameters !== null) {
        return parameters;
      }
      parameters = {};
      var pairs = parseUrlParams();
      var unesc = window.decodeURIComponent ? decodeURIComponent : unescape;
      for (var i = 0, j = pairs.length; i < j; ++i) {
        var pos = pairs[i].indexOf('=');
        if (pos === -1) {
          continue;
        }
        var argName = pairs[i].substring(0, pos);
        var value = pairs[i].substring(pos + 1);
        // difference to IG_Prefs, is that args doesn't replace spaces in
        // argname. Unclear on if it should do:
        // argname = argname.replace(/\+/g, " ");
        value = value.replace(/\+/g, " ");
        parameters[argName] = unesc(value);
      }
      return parameters;
    },

    /**
     * Creates a closure that is suitable for passing as a callback.
     * Any number of arguments
     * may be passed to the callback;
     * they will be received in the order they are passed in.
     *
     * @param {Object} scope The execution scope; may be null if there is no
     *     need to associate a specific instance of an object with this
     *     callback
     * @param {Function} callback The callback to invoke when this is run;
     *     any arguments passed in will be passed after your initial arguments
     * @param {Object} var_args Initial arguments to be passed to the callback
     *
     * @member gadgets.util
     * @private Implementation detail.
     */
    makeClosure : function (scope, callback, var_args) {
      // arguments isn't a real array, so we copy it into one.
      var baseArgs = [];
      for (var i = 2, j = arguments.length; i < j; ++i) {
       baseArgs.push(arguments[i]);
      }
      return function() {
        // append new arguments.
        var tmpArgs = baseArgs.slice();
        for (var i = 0, j = arguments.length; i < j; ++i) {
          tmpArgs.push(arguments[i]);
        }
        return callback.apply(scope, tmpArgs);
      };
    },

    /**
     * Utility function for generating an "enum" from an array.
     *
     * @param {Array.<String>} values The values to generate.
     * @return {Map&lt;String,String&gt;} An object with member fields to handle
     *   the enum.
     *
     * @private Implementation detail.
     */
    makeEnum : function (values) {
      var obj = {};
      for (var i = 0, v; v = values[i]; ++i) {
        obj[v] = v;
      }
      return obj;
    },

    /**
     * Gets the feature parameters.
     *
     * @param {String} feature The feature to get parameters for
     * @return {Object} The parameters for the given feature, or null
     *
     * @member gadgets.util
     */
    getFeatureParameters : function (feature) {
      return typeof features[feature] === "undefined"
          ? null : features[feature];
    },

    /**
     * Returns whether the current feature is supported.
     *
     * @param {String} feature The feature to test for
     * @return {Boolean} True if the feature is supported
     *
     * @member gadgets.util
     */
    hasFeature : function (feature) {
      return typeof features[feature] !== "undefined";
    },

    /**
     * Registers an onload handler.
     * @param {Function} callback The handler to run
     *
     * @member gadgets.util
     */
    registerOnLoadHandler : function (callback) {
      onLoadHandlers.push(callback);
    },

    /**
     * Runs all functions registered via registerOnLoadHandler.
     * @private Only to be used by the container, not gadgets.
     */
    runOnLoadHandlers : function () {
      for (var i = 0, j = onLoadHandlers.length; i < j; ++i) {
        onLoadHandlers[i]();
      }
    },

    /**
     * Escapes the input using html entities to make it safer.
     *
     * If the input is a string, uses gadgets.util.escapeString.
     * If it is an array, calls escape on each of the array elements
     * if it is an object, will only escape all the mapped keys and values if
     * the opt_escapeObjects flag is set. This operation involves creating an
     * entirely new object so only set the flag when the input is a simple
     * string to string map.
     * Otherwise, does not attempt to modify the input.
     *
     * @param {Object} input The object to escape
     * @param {Boolean} opt_escapeObjects Whether to escape objects.
     * @return {Object} The escaped object
     * @private Only to be used by the container, not gadgets.
     */
    escape : function(input, opt_escapeObjects) {
      if (!input) {
        return input;
      } else if (typeof input === "string") {
        return gadgets.util.escapeString(input);
      } else if (typeof input === "array") {
        for (var i = 0, j = input.length; i < j; ++i) {
          input[i] = gadgets.util.escape(input[i]);
        }
      } else if (typeof input === "object" && opt_escapeObjects) {
        var newObject = {};
        for (var field in input) if (input.hasOwnProperty(field)) {
          newObject[gadgets.util.escapeString(field)]
              = gadgets.util.escape(input[field], true);
        }
        return newObject;
      }
      return input;
    },

    /**
     * Escapes the input using html entities to make it safer.
     *
     * Currently not in the spec -- future proposals may change
     * how this is handled.
     *
     * TODO: Parsing the string would probably be more accurate and faster than
     * a bunch of regular expressions.
     *
     * @param {String} str The string to escape
     * @return {String} The escaped string
     */
    escapeString : function(str) {
      var out = [], ch, shouldEscape;
      for (var i = 0, j = str.length; i < j; ++i) {
        ch = str.charCodeAt(i);
        shouldEscape = escapeCodePoints[ch];
        if (shouldEscape === true) {
          out.push("&#", ch, ";");
        } else if (shouldEscape !== false) {
          // undefined or null are OK.
          out.push(str.charAt(i));
        }
      }
      return out.join("");
    },

    /**
     * Reverses escapeString
     *
     * @param {String} str The string to unescape.
     */
    unescapeString : function(str) {
      return str.replace(/&#([0-9]+);/g, unescapeEntity);
    }
  };
}();
// Initialize url parameters so that hash data is pulled in before it can be
// altered by a click.
gadgets.util.getUrlParameters();

/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

/**
 * @fileoverview
 * The global object gadgets.json contains two methods.
 *
 * gadgets.json.stringify(value) takes a JavaScript value and produces a JSON
 * text. The value must not be cyclical.
 *
 * gadgets.json.parse(text) takes a JSON text and produces a JavaScript value.
 * It will return false if there is an error.
*/

var gadgets = gadgets || {};

/**
 * @static
 * @class Provides operations for translating objects to and from JSON.
 * @name gadgets.json
 */

/**
 * Port of the public domain JSON library by Douglas Crockford.
 * See: http://www.json.org/json2.js
 */
gadgets.json = function () {

  /**
   * Formats integers to 2 digits.
   * @param {Number} n
   */
  function f(n) {
    return n < 10 ? '0' + n : n;
  }

  Date.prototype.toJSON = function () {
    return [this.getUTCFullYear(), '-',
           f(this.getUTCMonth() + 1), '-',
           f(this.getUTCDate()), 'T',
           f(this.getUTCHours()), ':',
           f(this.getUTCMinutes()), ':',
           f(this.getUTCSeconds()), 'Z'].join("");
  };

  // table of character substitutions
  var m = {
    '\b': '\\b',
    '\t': '\\t',
    '\n': '\\n',
    '\f': '\\f',
    '\r': '\\r',
    '"' : '\\"',
    '\\': '\\\\'
  };

  /**
   * Converts a json object into a string.
   */
  function stringify(value) {
    var a,          // The array holding the partial texts.
        i,          // The loop counter.
        k,          // The member key.
        l,          // Length.
        r = /["\\\x00-\x1f\x7f-\x9f]/g,
        v;          // The member value.

    switch (typeof value) {
    case 'string':
    // If the string contains no control characters, no quote characters, and no
    // backslash characters, then we can safely slap some quotes around it.
    // Otherwise we must also replace the offending characters with safe ones.
      return r.test(value) ?
          '"' + value.replace(r, function (a) {
            var c = m[a];
            if (c) {
              return c;
            }
            c = a.charCodeAt();
            return '\\u00' + Math.floor(c / 16).toString(16) +
                (c % 16).toString(16);
            }) + '"'
          : '"' + value + '"';
    case 'number':
    // JSON numbers must be finite. Encode non-finite numbers as null.
      return isFinite(value) ? String(value) : 'null';
    case 'boolean':
    case 'null':
      return String(value);
    case 'object':
    // Due to a specification blunder in ECMAScript,
    // typeof null is 'object', so watch out for that case.
      if (!value) {
        return 'null';
      }
      // toJSON check removed; re-implement when it doesn't break other libs.
      a = [];
      if (typeof value.length === 'number' &&
          !(value.propertyIsEnumerable('length'))) {
        // The object is an array. Stringify every element. Use null as a
        // placeholder for non-JSON values.
        l = value.length;
        for (i = 0; i < l; i += 1) {
          a.push(stringify(value[i]) || 'null');
        }
        // Join all of the elements together and wrap them in brackets.
        return '[' + a.join(',') + ']';
      }
      // Otherwise, iterate through all of the keys in the object.
      for (k in value) if (value.hasOwnProperty(k)) {
        if (typeof k === 'string') {
          v = stringify(value[k]);
          if (v) {
            a.push(stringify(k) + ':' + v);
          }
        }
      }
      // Join all of the member texts together and wrap them in braces.
      return '{' + a.join(',') + '}';
    }
  }

  return {
    stringify: stringify,
    parse: function (text) {
// Parsing happens in three stages. In the first stage, we run the text against
// regular expressions that look for non-JSON patterns. We are especially
// concerned with '()' and 'new' because they can cause invocation, and '='
// because it can cause mutation. But just to be safe, we want to reject all
// unexpected forms.

// We split the first stage into 4 regexp operations in order to work around
// crippling inefficiencies in IE's and Safari's regexp engines. First we
// replace all backslash pairs with '@' (a non-JSON character). Second, we
// replace all simple value tokens with ']' characters. Third, we delete all
// open brackets that follow a colon or comma or that begin the text. Finally,
// we look to see that the remaining characters are only whitespace or ']' or
// ',' or ':' or '{' or '}'. If that is so, then the text is safe for eval.

      if (/^[\],:{}\s]*$/.test(text.replace(/\\["\\\/b-u]/g, '@').
          replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').
          replace(/(?:^|:|,)(?:\s*\[)+/g, ''))) {
        return eval('(' + text + ')');
      }
      // If the text is not JSON parseable, then return false.

      return false;
    }
  };
}();

/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 */

/**
 * @fileoverview Remote procedure call library for gadget-to-container,
 * container-to-gadget, and gadget-to-gadget communication.
 */

var gadgets = gadgets || {};

/**
 * @static
 * @class Provides operations for making rpc calls.
 * @name gadgets.rpc
 */
gadgets.rpc = function() {
  var services = {};
  var iframePool = [];
  var relayUrl = {};
  var useLegacyProtocol = {};
  var authToken = {};
  var callId = 0;
  var callbacks = {};

  var params = gadgets.util.getUrlParameters();
  authToken['..'] = params.rpctoken || params.ifpctok || 0;

  // Pick the most efficient RPC relay mechanism
  var relayChannel = typeof document.postMessage === 'function' ? 'dpm' :
                     typeof window.postMessage === 'function' ? 'wpm' :
                     'ifpc';
  if (relayChannel === 'dpm' || relayChannel === 'wpm') {
    document.addEventListener('message', function(packet) {
      // TODO validate packet.domain for security reasons
      process(gadgets.json.parse(packet.data));
    }, false);
  }

  // Default RPC handler
  services[''] = function() {
    throw new Error('Unknown RPC service: ' + this.s);
  };

  // Special RPC handler for callbacks
  services['__cb'] = function(callbackId, result) {
    var callback = callbacks[callbackId];
    if (callback) {
      delete callbacks[callbackId];
      callback(result);
    }
  };

  /**
   * Encodes arguments for the legacy IFPC wire format.
   *
   * @param {Object} args
   * @return {String} the encoded args
   */
  function encodeLegacyData(args) {
    var stringify = gadgets.json.stringify;
    var argsEscaped = [];
    for(var i = 0, j = args.length; i < j; ++i) {
      argsEscaped.push(encodeURIComponent(stringify(args[i])));
    }
    return argsEscaped.join('&');
  }

  /**
   * Helper function to process an RPC request
   * @param {Object} rpc RPC request object
   * @private
   */
  function process(rpc) {
    if (rpc && typeof rpc.s === 'string' && typeof rpc.f === 'string' &&
        rpc.a instanceof Array) {
      // Validate auth token.
      if (authToken[rpc.f]) {
        // We allow type coercion here because all the url params are strings.
        if (authToken[rpc.f] != rpc.t) {
          throw new Error("Invalid auth token.");
        }
      }

      // The Gecko engine used by FireFox etc. allows an IFrame to directly call
      // methods on the frameElement property added by the container page even
      // if their domains don't match.
      // Here we try to set up a relay channel using the frameElement technique
      // to greatly reduce the latency of cross-domain calls if the postMessage
      // method is not supported.
      if (relayChannel === 'ifpc') {
        if (rpc.f === '..') {
          // Container-to-gadget call
          try {
            var fel = window.frameElement;
            if (typeof fel.__g2c_rpc === 'function' &&
                typeof fel.__g2c_rpc.__c2g_rpc != 'function') {
              fel.__g2c_rpc.__c2g_rpc = function(args) {
                process(gadgets.json.parse(args));
              };
            }
          } catch (e) {
          }
        } else {
          // Gadget-to-container call
          var iframe = document.getElementById(rpc.f);
          if (iframe && typeof iframe.__g2c_rpc != 'function') {
            iframe.__g2c_rpc = function(args) {
              process(gadgets.json.parse(args));
            };
          }
        }
      }

      var result = (services[rpc.s] || services['']).apply(rpc, rpc.a);
      if (rpc.c) {
        gadgets.rpc.call(rpc.f, '__cb', null, rpc.c, result);
      }
    }
  }

  /**
   * Helper function to emit an invisible IFrame.
   * @param {String} src SRC attribute of the IFrame to emit.
   * @private
   */
  function emitInvisibleIframe(src) {
    var iframe;
    // Recycle IFrames
    for (var i = iframePool.length - 1; i >=0; --i) {
      var ifr = iframePool[i];
      try {
	      if (ifr && (ifr.recyclable || ifr.readyState === 'complete')) {
	        ifr.parentNode.removeChild(ifr);
	        if (window.ActiveXObject) {
	          // For MSIE, delete any iframes that are no longer being used. MSIE
	          // cannot reuse the IFRAME because a navigational click sound will
	          // be triggered when we set the SRC attribute.
	          // Other browsers scan the pool for a free iframe to reuse.
	          iframePool[i] = ifr = null;
	          iframePool.splice(i, 1);
	        } else {
	          ifr.recyclable = false;
	          iframe = ifr;
	          break;
	        }
	      }
      } catch (e) {
      	// Ignore; IE7 throws an exception when trying to read readyState and
      	// readyState isn't set.
      }
    }
    // Create IFrame if necessary
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.style.border = iframe.style.width = iframe.style.height = '0px';
      iframe.style.visibility = 'hidden';
      iframe.style.position = 'absolute';
      iframe.onload = function() { this.recyclable = true; };
      iframePool.push(iframe);
    }
    iframe.src = src;
    setTimeout(function() { document.body.appendChild(iframe); }, 0);
  }

  // gadgets.config might not be available, such as when serving container js.
  if (gadgets.config) {
    /**
     * Initializes RPC from the provided configuration.
     */
    function init(config) {
      // Allow for wild card parent relay files as long as it's from a
      // white listed domain. This is enforced by the rendering servlet.
      if (config.rpc.parentRelayUrl.substring(0, 7) === 'http://') {
        relayUrl['..'] = config.rpc.parentRelayUrl;
      } else {
        // It's a relative path, and we must append to the parent.
        // We're relying on the server validating the parent parameter in this
        // case. Because of this, parent may only be passed in the query, not
        // the fragment.
        var params = document.location.search.substring(0).split("&");
        var parentParam = "";
        for (var i = 0, param; param = params[i]; ++i) {
          // Only the first parent can be validated.
          if (param.indexOf("parent=") === 0) {
            parentParam = decodeURIComponent(param.substring(7));
            break;
          }
        }
        relayUrl['..'] = parentParam + config.rpc.parentRelayUrl;
      }
      useLegacyProtocol['..'] = !!config.rpc.useLegacyProtocol;
    }

    var requiredConfig = {
      parentRelayUrl : gadgets.config.NonEmptyStringValidator
    };
    gadgets.config.register("rpc", requiredConfig, init);
  }

  return /** @scope gadgets.rpc */ {
    /**
     * Registers an RPC service.
     * @param {String} serviceName Service name to register.
     * @param {Function} handler Service handler.
     *
     * @member gadgets.rpc
     */
    register: function(serviceName, handler) {
      services[serviceName] = handler;
    },

    /**
     * Unregisters an RPC service.
     * @param {String} serviceName Service name to unregister.
     *
     * @member gadgets.rpc
     */
    unregister: function(serviceName) {
      delete services[serviceName];
    },

    /**
     * Registers a default service handler to processes all unknown
     * RPC calls which raise an exception by default.
     * @param {Function} handler Service handler.
     *
     * @member gadgets.rpc
     */
    registerDefault: function(handler) {
      services[''] = handler;
    },

    /**
     * Unregisters the default service handler. Future unknown RPC
     * calls will fail silently.
     *
     * @member gadgets.rpc
     */
    unregisterDefault: function() {
      delete services[''];
    },

    /**
     * Calls an RPC service.
     * @param {String} targetId Module Id of the RPC service provider.
     *                          Empty if calling the parent container.
     * @param {String} serviceName Service name to call.
     * @param {Function|null} callback Callback function (if any) to process
     *                                 the return value of the RPC request.
     * @param {*} var_args Parameters for the RPC request.
     *
     * @member gadgets.rpc
     */
    call: function(targetId, serviceName, callback, var_args) {
      ++callId;
      targetId = targetId || '..';
      if (callback) {
        callbacks[callId] = callback;
      }
      var from;
      if (targetId === '..') {
        from = window.name;
      } else {
        from = '..';
      }
      // Not used by legacy, create it anyway...
      var rpcData = gadgets.json.stringify({
        s: serviceName,
        f: from,
        c: callback ? callId : 0,
        a: Array.prototype.slice.call(arguments, 3),
        t: authToken[targetId]
      });

      switch (relayChannel) {
      case 'dpm': // use document.postMessage
        var targetDoc = targetId === '..' ? parent.document :
                                            frames[targetId].document;
        targetDoc.postMessage(rpcData);
        break;
      case 'wpm': // use window.postMessage
        var targetWin = targetId === '..' ? parent : frames[targetId];
        targetWin.postMessage(rpcData);
        break;
      default: // use 'ifpc' as a fallback mechanism
        // Try the frameElement channel if available
        try {
          if (from === '..') {
            // Container-to-gadget
            var iframe = document.getElementById(targetId);
            if (typeof iframe.__g2c_rpc.__c2g_rpc === 'function') {
              iframe.__g2c_rpc.__c2g_rpc(rpcData);
              return;
            }
          } else {
            // Gadget-to-container
            if (typeof window.frameElement.__g2c_rpc === 'function') {
              window.frameElement.__g2c_rpc(rpcData);
              return;
            }
          }
        } catch (e) {
        }

        var relay = gadgets.rpc.getRelayUrl(targetId);

        // TODO split message if too long
        var src;
        if (useLegacyProtocol[targetId]) {
          // #iframe_id&callId&num_packets&packet_num&block_of_data
          src = [relay, '#', encodeLegacyData([from, callId, 1, 0,
                 encodeLegacyData([from, serviceName, '', '', from].concat(
                 Array.prototype.slice.call(arguments, 3)))])].join('');
        } else {
          // # targetId & sourceId@callId & packetNum & packetId & packetData
          src = [relay, '#', targetId, '&', from, '@', callId,
                 '&1&0&', encodeURIComponent(rpcData)].join('');
        }
        emitInvisibleIframe(src);
      }
    },

    /**
     * Gets the relay URL of a target frame.
     * @param {String} targetId Name of the target frame.
     * @return {String|undefined} Relay URL of the target frame.
     *
     * @member gadgets.rpc
     */
    getRelayUrl: function(targetId) {
      return relayUrl[targetId];
    },

    /**
     * Sets the relay URL of a target frame.
     * @param {String} targetId Name of the target frame.
     * @param {String} url Full relay URL of the target frame.
     * @param {Boolean} opt_useLegacy True if this relay needs the legacy IFPC
     *     wire format.
     *
     * @member gadgets.rpc
     */
    setRelayUrl: function(targetId, url, opt_useLegacy) {
      relayUrl[targetId] = url;
      useLegacyProtocol[targetId] = !!opt_useLegacy;
    },

    /**
     * Sets the auth token of a target frame.
     * @param {String} targetId Name of the target frame.
     * @param {String} token The authentication token to use for all
     *     calls to or from this target id.
     *
     * @member gadgets.rpc
     */
    setAuthToken: function(targetId, token) {
      authToken[targetId] = token;
    },

    /**
     * Gets the RPC relay mechanism.
     * @return {String} RPC relay mechanism. Supported types:
     *                  'wpm' - Use window.postMessage (defined by HTML5)
     *                  'dpm' - Use document.postMessage (defined by an early
     *                          draft of HTML5 and implemented by Opera)
     *                  'ifpc' - Use invisible IFrames
     *
     * @member gadgets.rpc
     */
    getRelayChannel: function() {
      return relayChannel;
    },

    /**
     * Receives and processes an RPC request. (Not to be used directly.)
     * @param {Array.<String>} fragment An RPC request fragment encoded as
     *        an array. The first 4 elements are target id, source id & call id,
     *        total packet number, packet id. The last element stores the actual
     *        JSON-encoded and URI escaped packet data.
     *
     * @member gadgets.rpc
     */
    receive: function(fragment) {
      if (fragment.length > 4) {
        // TODO parse fragment[1..3] to merge multi-fragment messages
        process(gadgets.json.parse(
            decodeURIComponent(fragment[fragment.length - 1])));
      }
    }
  };
}();

/**
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 */

/**
 * @fileoverview Functions for setting, getting and deleting cookies
 */

/**
 * Namespace for cookie functions
 */
// goog.provide('goog.net.cookies');
// TODO: find the official solution for a cookies library
var goog = goog || {};
goog.net = goog.net || {};
goog.net.cookies = goog.net.cookies || {};


goog.JsType_ = {
  UNDEFINED: 'undefined'
};

goog.isDef = function(val) {
  return typeof val != goog.JsType_.UNDEFINED;
};


/**
 * Sets a cookie.
 * The max_age can be -1 to set a session cookie. To remove and expire cookies,
 * use remove() instead.
 *
 * @param {string} name The cookie name.
 * @param {string} value The cookie value.
 * @param {number} opt_maxAge The max age in seconds (from now). Use -1 to set
 *                            a session cookie. If not provided, the default is
 *                            -1 (i.e. set a session cookie).
 * @param {string} opt_path The path of the cookie, or null to not specify a
 *                          path attribute (browser will use the full request
 *                          path). If not provided, the default is '/' (i.e.
 *                          path=/).
 * @param {string} opt_domain The domain of the cookie, or null to not specify
 *                            a domain attribute (browser will use the full
 *                            request host name). If not provided, the default
 *                            is null (i.e. let browser use full request host
 *                            name).
 */
goog.net.cookies.set = function(name, value, opt_maxAge, opt_path, opt_domain) {
  // we do not allow '=' or ';' in the name
  if (/;=/g.test(name)) {
    throw new Error('Invalid cookie name "' + name + '"');
  }
  // we do not allow ';' in value
  if (/;/g.test(value)) {
    throw new Error('Invalid cookie value "' + value + '"');
  }

  if (!goog.isDef(opt_maxAge)) {
    opt_maxAge = -1;
  }

  var domainStr = opt_domain ? ';domain=' + opt_domain : '';
  var pathStr = opt_path ? ';path=' + opt_path : '';

  var expiresStr;

  // Case 1: Set a session cookie.
  if (opt_maxAge < 0) {
    expiresStr = '';

  // Case 2: Expire the cookie.
  // Note: We don't tell people about this option in the function doc because
  // we prefer people to use ExpireCookie() to expire cookies.
  } else if (opt_maxAge == 0) {
    // Note: Don't use Jan 1, 1970 for date because NS 4.76 will try to convert
    // it to local time, and if the local time is before Jan 1, 1970, then the
    // browser will ignore the Expires attribute altogether.
    var pastDate = new Date(1970, 1 /*Feb*/, 1);  // Feb 1, 1970
    expiresStr = ';expires=' + pastDate.toUTCString();

  // Case 3: Set a persistent cookie.
  } else {
    var futureDate = new Date((new Date).getTime() + opt_maxAge * 1000);
    expiresStr = ';expires=' + futureDate.toUTCString();
  }

  document.cookie = name + '=' + value + domainStr + pathStr + expiresStr;
};


/**
 * Returns the value for the first cookie with the given name
 * @param {string} name The name of the cookie to get
 * @param {string} opt_default If not found this is returned instead.
 * @return {string|undefined} The value of the cookie. If no cookie is set this
 *                            returns opt_default or undefined if opt_default is
 *                            not provided.
 */
goog.net.cookies.get = function(name, opt_default) {
  var nameEq = name + "=";
  var cookie = String(document.cookie);
  for (var pos = -1; (pos = cookie.indexOf(nameEq, pos + 1)) >= 0;) {
    var i = pos;
    // walk back along string skipping whitespace and looking for a ; before
    // the name to make sure that we don't match cookies whose name contains
    // the given name as a suffix.
    while (--i >= 0) {
      var ch = cookie.charAt(i);
      if (ch == ';') {
        i = -1;  // indicate success
        break;
      }
    }
    if (i == -1) {  // first cookie in the string or we found a ;
      var end = cookie.indexOf(';', pos);
      if (end < 0) {
        end = cookie.length;
      }
      return cookie.substring(pos + nameEq.length, end);
    }
  }
  return opt_default;
};


/**
 * Removes and expires a cookie.
 *
 * @param {string} name The cookie name.
 * @param {string} opt_path The path of the cookie, or null to expire a cookie
 *                          set at the full request path. If not provided, the
 *                          default is '/' (i.e. path=/).
 * @param {string} opt_domain The domain of the cookie, or null to expire a
 *                            cookie set at the full request host name. If not
 *                            provided, the default is null (i.e. cookie at
 *                            full request host name).
 */
goog.net.cookies.remove = function(name, opt_path, opt_domain) {
  var rv = goog.net.cookies.containsKey(name);
  goog.net.cookies.set(name, '', 0, opt_path, opt_domain);
  return rv;
};


/**
 * Gets the names and values for all the cookies
 * @private
 * @return {Object} An object with keys and values
 */
goog.net.cookies.getKeyValues_ = function() {
  var cookie = String(document.cookie);
  var parts = cookie.split(/\s*;\s*/);
  var keys = [], values = [], index, part;
  for (var i = 0; part = parts[i]; i++) {
    index = part.indexOf('=');

    if (index == -1) { // empty name
      keys.push('');
      values.push(part);
    } else {
      keys.push(part.substring(0, index));
      values.push(part.substring(index + 1));
    }
  }
  return {keys: keys, values: values};
};


/**
 * Gets the names for all the cookies
 * @return {Array} An array with the names of the cookies
 */
goog.net.cookies.getKeys = function() {
  return goog.net.cookies.getKeyValues_().keys;
};


/**
 * Gets the values for all the cookies
 * @return {Array} An array with the values of the cookies
 */
goog.net.cookies.getValues = function() {
  return goog.net.cookies.getKeyValues_().values;
};


/**
 * Whether there are any cookies for this document
 * @return {boolean}
 */
goog.net.cookies.isEmpty = function() {
  return document.cookie == '';
};


/**
 * Returns the number of cookies for this document
 * @return {number}
 */
goog.net.cookies.getCount = function() {
  var cookie = String(document.cookie);
  if (cookie == '') {
    return 0;
  }
  var parts = cookie.split(/\s*;\s*/);
  return parts.length;
};


/**
 * Returns whether there is a cookie with the given name
 * @param {string} key The name of the cookie to test for
 * @return {boolean}
 */
goog.net.cookies.containsKey = function(key) {
  var sentinel = {};
  // if get does not find the key it returns the default value. We therefore
  // compare the result with an object to ensure we do not get any false
  // positives.
  return goog.net.cookies.get(key, sentinel) !== sentinel;
};


/**
 * Returns whether there is a cookie with the given value. (This is an O(n)
 * operation.)
 * @param {string} value The value to check for
 * @return {boolean}
 */
goog.net.cookies.containsValue = function(value) {
  // this O(n) in any case so lets do the trivial thing.
  var values = goog.net.cookies.getKeyValues_().values;
  for (var i = 0; i < values.length; i++) {
    if (values[i] == value) {
      return true;
    }
  }
  return false;
};


/**
 * Removes all cookies for this document
 */
goog.net.cookies.clear = function() {
  var keys = goog.net.cookies.getKeyValues_().keys;
  for (var i = keys.length - 1; i >= 0; i--) {
    goog.net.cookies.remove(keys[i]);
  }
};

/**
 * Static constant for the size of cookies. Per the spec, there's a 4K limit
 * to the size of a cookie. To make sure users can't break this limit, we
 * should truncate long cookies at 3950 bytes, to be extra careful with dumb
 * browsers/proxies that interpret 4K as 4000 rather than 4096
 * @type number
 */
goog.net.cookies.MAX_COOKIE_LENGTH = 3950;
/**
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 */

/**
 * @fileoverview Utility functions for the Open Gadget Container
 */

Function.prototype.inherits = function(parentCtor) {
  function tempCtor() {};
  tempCtor.prototype = parentCtor.prototype;
  this.superClass_ = parentCtor.prototype;
  this.prototype = new tempCtor();
  this.prototype.constructor = this;
};/**
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 */

/**
 * @fileoverview Open Gadget Container
 */

var gadgets = gadgets || {};

gadgets.error = {};
gadgets.error.SUBCLASS_RESPONSIBILITY = 'subclass responsibility';
gadgets.error.TO_BE_DONE = 'to be done';

gadgets.log = function(message) {
  if (window.console && console.log) {
    console.log(message);
  } else {
    var logEntry = document.createElement('div');
    logEntry.className = 'gadgets-log-entry';
    logEntry.innerHTML = message;
    document.body.appendChild(logEntry);
  }
};

/**
 * Calls an array of asynchronous functions and calls the continuation
 * function when all are done.
 * @param {Array} functions Array of asynchronous functions, each taking
 *     one argument that is the continuation function that handles the result
 *     That is, each function is something like the following:
 *     function(continuation) {
 *       // compute result asynchronously
 *       continuation(result);
 *     }
 * @param {Function} continuation Function to call when all results are in.  It
 *     is pass an array of all results of all functions
 * @param {Object} opt_this Optional object used as "this" when calling each
 *     function
 */
gadgets.callAsyncAndJoin = function(functions, continuation, opt_this) {
  var pending = functions.length;
  var results = [];
  for (var i = 0; i < functions.length; i++) {
    // we need a wrapper here because i changes and we need once index
    // variable per closure
    var wrapper = function(index) {
      functions[index].call(opt_this, function(result) {
        results[index] = result;
        if (--pending == 0) {
          continuation(results);
        }
      });
    };
    wrapper(i);
  }
};


// ----------
// Extensible

gadgets.Extensible = function() {
};

/**
 * Sets the dependencies.
 * @param {Object} dependencies Object whose properties are set on this
 *     container as dependencies
 */
gadgets.Extensible.prototype.setDependencies = function(dependencies) {
  for (var p in dependencies) {
    this[p] = dependencies[p];
  }
};

/**
 * Returns a dependency given its name.
 * @param {String} name Name of dependency
 * @return {Object} Dependency with that name or undefined if not found
 */
gadgets.Extensible.prototype.getDependencies = function(name) {
  return this[name];
};



// -------------
// UserPrefStore

/**
 * User preference store interface.
 * @constructor
 */
gadgets.UserPrefStore = function() {
};

/**
 * Gets all user preferences of a gadget.
 * @param {Object} gadget Gadget object
 * @return {Object} All user preference of given gadget
 */
gadgets.UserPrefStore.prototype.getPrefs = function(gadget) {
  throw Error(gadgets.error.SUBCLASS_RESPONSIBILITY);
};

/**
 * Saves user preferences of a gadget in the store.
 * @param {Object} gadget Gadget object
 * @param {Object} prefs User preferences
 */
gadgets.UserPrefStore.prototype.savePrefs = function(gadget) {
  throw Error(gadgets.error.SUBCLASS_RESPONSIBILITY);
};


// -------------
// DefaultUserPrefStore

/**
 * User preference store implementation.
 * TODO: Turn this into a real implementation that is production safe
 * @constructor
 */
gadgets.DefaultUserPrefStore = function() {
  gadgets.UserPrefStore.call(this);
};
gadgets.DefaultUserPrefStore.inherits(gadgets.UserPrefStore);

gadgets.DefaultUserPrefStore.prototype.getPrefs = function(gadget) { };

gadgets.DefaultUserPrefStore.prototype.savePrefs = function(gadget) { };


// -------------
// GadgetService

/**
 * Interface of service provided to gadgets for resizing gadgets,
 * setting title, etc.
 * @constructor
 */
gadgets.GadgetService = function() {
};

gadgets.GadgetService.prototype.setHeight = function(elementId, height) {
  throw Error(gadgets.error.SUBCLASS_RESPONSIBILITY);
};

gadgets.GadgetService.prototype.setTitle = function(gadget, title) {
  throw Error(gadgets.error.SUBCLASS_RESPONSIBILITY);
};

gadgets.GadgetService.prototype.setUserPref = function(id) {
  throw Error(gadgets.error.SUBCLASS_RESPONSIBILITY);
};


// ----------------
// IfrGadgetService

/**
 * Base implementation of GadgetService.
 * @constructor
 */
gadgets.IfrGadgetService = function() {
  gadgets.GadgetService.call(this);
  gadgets.rpc.register('resize_iframe', this.setHeight);
  gadgets.rpc.register('set_pref', this.setUserPref);
  gadgets.rpc.register('set_title', this.setTitle);
  gadgets.rpc.register('requestNavigateTo', this.requestNavigateTo);
};

gadgets.IfrGadgetService.inherits(gadgets.GadgetService);

gadgets.IfrGadgetService.prototype.setHeight = function(height) {
  if (height > gadgets.container.maxheight_) {
    height = gadgets.container.maxheight_;
  }

  var element = document.getElementById(this.f);
  if (element) {
    element.style.height = height + 'px';
  }
};

gadgets.IfrGadgetService.prototype.setTitle = function(title) {
  var element = document.getElementById(this.f + '_title');
  if (element) {
    element.innerHTML = title.replace(/&/g, '&amp;').replace(/</g, '&lt;');
  }
};

/**
 * Sets one or more user preferences
 * @param {String} editToken
 * @param {String} name Name of user preference
 * @param {String} value Value of user preference
 * More names and values may follow
 */
gadgets.IfrGadgetService.prototype.setUserPref = function(editToken, name,
    value) {
  var id = this.getGadgetIdFromModuleId(this.f);
  var gadget = gadgets.container.getGadget(id);
  var prefs = gadget.getUserPrefs();
  for (var i = 1, j = arguments.length; i < j; i += 2) {
    prefs[arguments[i]] = arguments[i + 1];
  }
  gadget.setUserPrefs(prefs);
};

/**
 * Navigates the page to a new url based on a gadgets requested view and
 * parameters.
 */
gadgets.IfrGadgetService.prototype.requestNavigateTo = function(view,
    opt_params) {
  var id = this.getGadgetIdFromModuleId(this.f);
  var url = this.getUrlForView(view);

  if (opt_params) {
    var paramStr = JSON.stringify(opt_params);
    if (paramStr.length > 0) {
      url += '&appParams=' + encodeURIComponent(paramStr);
    }
  }

  if (url && document.location.href.indexOf(url) == -1) {
    document.location.href = url;
  }
};

/**
 * This is a silly implementation that will need to be overriden by almost all
 * real containers.
 * TODO: Find a better default for this function
 *
 * @param view The view name to get the url for
 */
gadgets.IfrGadgetService.prototype.getUrlForView = function(
    view) {
  if (view === 'canvas') {
    return '/canvas';
  } else if (view === 'profile') {
    return '/profile';
  } else {
    return null;
  }
}

gadgets.IfrGadgetService.prototype.getGadgetIdFromModuleId = function(
    moduleId) {
  // Quick hack to extract the gadget id from module id
  return parseInt(moduleId.match(/_([0-9]+)$/)[1], 10);
};


// -------------
// LayoutManager

/**
 * Layout manager interface.
 * @constructor
 */
gadgets.LayoutManager = function() {
};

/**
 * Gets the HTML element that is the chrome of a gadget into which the content
 * of the gadget can be rendered.
 * @param {Object} gadget Gadget instance
 * @return {Object} HTML element that is the chrome for the given gadget
 */
gadgets.LayoutManager.prototype.getGadgetChrome = function(gadget) {
  throw Error(gadgets.error.SUBCLASS_RESPONSIBILITY);
};

// -------------------
// StaticLayoutManager

/**
 * Static layout manager where gadget ids have a 1:1 mapping to chrome ids.
 * @constructor
 */
gadgets.StaticLayoutManager = function() {
  gadgets.LayoutManager.call(this);
};

gadgets.StaticLayoutManager.inherits(gadgets.LayoutManager);

/**
 * Sets chrome ids, whose indexes are gadget instance ids (starting from 0).
 * @param {Array} gadgetIdToChromeIdMap Gadget id to chrome id map
 */
gadgets.StaticLayoutManager.prototype.setGadgetChromeIds =
    function(gadgetChromeIds) {
  this.gadgetChromeIds_ = gadgetChromeIds;
};

gadgets.StaticLayoutManager.prototype.getGadgetChrome = function(gadget) {
  var chromeId = this.gadgetChromeIds_[gadget.id];
  return chromeId ? document.getElementById(chromeId) : null;
};


// ----------------------
// FloatLeftLayoutManager

/**
 * FloatLeft layout manager where gadget ids have a 1:1 mapping to chrome ids.
 * @constructor
 * @param {String} layoutRootId Id of the element that is the parent of all
 *     gadgets.
 */
gadgets.FloatLeftLayoutManager = function(layoutRootId) {
  gadgets.LayoutManager.call(this);
  this.layoutRootId_ = layoutRootId;
};

gadgets.FloatLeftLayoutManager.inherits(gadgets.LayoutManager);

gadgets.FloatLeftLayoutManager.prototype.getGadgetChrome =
    function(gadget) {
  var layoutRoot = document.getElementById(this.layoutRootId_);
  if (layoutRoot) {
    var chrome = document.createElement('div');
    chrome.className = 'gadgets-gadget-chrome';
    chrome.style.cssFloat = 'left'
    layoutRoot.appendChild(chrome);
    return chrome;
  } else {
    return null;
  }
};


// ------
// Gadget

/**
 * Creates a new instance of gadget.  Optional parameters are set as instance
 * variables.
 * @constructor
 * @param {Object} params Parameters to set on gadget.  Common parameters:
 *    "specUrl": URL to gadget specification
 *    "private": Whether gadget spec is accessible only privately, which means
 *        browser can load it but not gadget server
 *    "spec": Gadget Specification in XML
 *    "viewParams": a javascript object containing attribute value pairs
 *        for this gadgets
 *    "secureToken": an encoded token that is passed on the URL hash
 *    "hashData": Query-string like data that will be added to the
 *        hash portion of the URL.
 *    "specVersion": a hash value used to add a v= param to allow for better caching
 *    "title": the default title to use for the title bar.
 *    "height": height of the gadget
 *    "width": width of the gadget
 *    "debug": send debug=1 to the gadget server, gets us uncompressed
 *        javascript
 */
gadgets.Gadget = function(params) {
  this.userPrefs_ = {};

  if (params) {
    for (var name in params)  if (params.hasOwnProperty(name)) {
      this[name] = params[name];
    }
  }
  if (!this.secureToken) {
    // Assume that the default security token implementation is
    // in use on the server.
    this.secureToken = 'john.doe:john.doe:appid:synd:url:0';
  }
};

gadgets.Gadget.prototype.getUserPrefs = function() {
  return this.userPrefs_;
};

gadgets.Gadget.prototype.setUserPrefs = function(userPrefs) {
  this.userPrefs_ = userPrefs;
  gadgets.container.userPrefStore.savePrefs(this);
};

gadgets.Gadget.prototype.getUserPref = function(name) {
  return this.userPrefs_[name];
};

gadgets.Gadget.prototype.setUserPref = function(name, value) {
  this.userPrefs_[name] = value;
  gadgets.container.userPrefStore.savePrefs(this);
};

gadgets.Gadget.prototype.render = function(chrome) {
  if (chrome) {
    this.getContent(function(content) {
      chrome.innerHTML = content;
    });
  }
};

gadgets.Gadget.prototype.getContent = function(continuation) {
  gadgets.callAsyncAndJoin([
      this.getTitleBarContent, this.getUserPrefsDialogContent,
      this.getMainContent], function(results) {
        continuation(results.join(''));
      }, this);
};

/**
 * Gets title bar content asynchronously or synchronously.
 * @param {Function} continutation Function that handles title bar content as
 *     the one and only argument
 */
gadgets.Gadget.prototype.getTitleBarContent = function(continuation) {
  throw Error(gadgets.error.SUBCLASS_RESPONSIBILITY);
};

/**
 * Gets user preferences dialog content asynchronously or synchronously.
 * @param {Function} continutation Function that handles user preferences
 *     content as the one and only argument
 */
gadgets.Gadget.prototype.getUserPrefsDialogContent = function(continuation) {
  throw Error(gadgets.error.SUBCLASS_RESPONSIBILITY);
};

/**
 * Gets gadget content asynchronously or synchronously.
 * @param {Function} continutation Function that handles gadget content as
 *     the one and only argument
 */
gadgets.Gadget.prototype.getMainContent = function(continuation) {
  throw Error(gadgets.error.SUBCLASS_RESPONSIBILITY);
};

/*
 * Gets additional parameters to append to the iframe url
 * Override this method if you need any custom params.
 */
gadgets.Gadget.prototype.getAdditionalParams = function() {
  return '';
}


// ---------
// IfrGadget

gadgets.IfrGadget = function(opt_params) {
  gadgets.Gadget.call(this, opt_params);
  this.serverBase_ = '../../' // default gadget server
};

gadgets.IfrGadget.inherits(gadgets.Gadget);

gadgets.IfrGadget.prototype.GADGET_IFRAME_PREFIX_ = 'remote_iframe_';

gadgets.IfrGadget.prototype.CONTAINER = 'default';

gadgets.IfrGadget.prototype.cssClassGadget = 'gadgets-gadget';
gadgets.IfrGadget.prototype.cssClassTitleBar = 'gadgets-gadget-title-bar';
gadgets.IfrGadget.prototype.cssClassTitle = 'gadgets-gadget-title';
gadgets.IfrGadget.prototype.cssClassTitleButtonBar =
    'gadgets-gadget-title-button-bar';
gadgets.IfrGadget.prototype.cssClassGadgetUserPrefsDialog =
    'gadgets-gadget-user-prefs-dialog';
gadgets.IfrGadget.prototype.cssClassGadgetUserPrefsDialogActionBar =
    'gadgets-gadget-user-prefs-dialog-action-bar';
gadgets.IfrGadget.prototype.cssClassTitleButton = 'gadgets-gadget-title-button';
gadgets.IfrGadget.prototype.cssClassGadgetContent = 'gadgets-gadget-content';
gadgets.IfrGadget.prototype.rpcToken = (0x7FFFFFFF * Math.random()) | 0;
gadgets.IfrGadget.prototype.rpcRelay = 'files/rpc_relay.html';

gadgets.IfrGadget.prototype.getTitleBarContent = function(continuation) {
  continuation('<div id="' + this.cssClassTitleBar + '-' + this.id +
      '" class="' + this.cssClassTitleBar + '"><span id="' +
      this.getIframeId() + '_title" class="' +
      this.cssClassTitle + '">' + (this.title ? this.title : 'Title') + '</span> | <span class="' +
      this.cssClassTitleButtonBar +
      '"><a href="#" onclick="gadgets.container.getGadget(' + this.id +
      ').handleOpenUserPrefsDialog();return false;" class="' + this.cssClassTitleButton +
      '">settings</a> <a href="#" onclick="gadgets.container.getGadget(' +
      this.id + ').handleToggle();return false;" class="' + this.cssClassTitleButton +
      '">toggle</a></span></div>');
};

gadgets.IfrGadget.prototype.getUserPrefsDialogContent = function(continuation) {
  continuation('<div id="' + this.getUserPrefsDialogId() + '" class="' +
      this.cssClassGadgetUserPrefsDialog + '"></div>');
};

gadgets.IfrGadget.prototype.setServerBase = function(url) {
  this.serverBase_ = url;
};

gadgets.IfrGadget.prototype.getServerBase = function() {
  return this.serverBase_;
};

gadgets.IfrGadget.prototype.getMainContent = function(continuation) {
  var iframeId = this.getIframeId();
  gadgets.rpc.setRelayUrl(iframeId, this.serverBase_ + this.rpcRelay);
  gadgets.rpc.setAuthToken(iframeId, this.rpcToken);
  continuation('<div class="' + this.cssClassGadgetContent + '"><iframe id="' +
      iframeId + '" name="' + iframeId + '" class="' + this.cssClassGadget +
      '" src="' + this.getIframeUrl() +
      '" frameborder="no" scrolling="no"' +
      (this.height ? ' height="' + this.height + '"' : '') +
      (this.width ? ' width="' + this.width + '"' : '') +
      '></iframe></div>');
};

gadgets.IfrGadget.prototype.getIframeId = function() {
  return this.GADGET_IFRAME_PREFIX_ + this.id;
};

gadgets.IfrGadget.prototype.getUserPrefsDialogId = function() {
  return this.getIframeId() + '_userPrefsDialog';
};

gadgets.IfrGadget.prototype.getIframeUrl = function() {
  return this.serverBase_ + 'ifr?' +
      'url=' + encodeURIComponent(this.specUrl) +
      '&container=' + this.CONTAINER +
      '&mid=' +  this.id +
      '&nocache=' + gadgets.container.nocache_ +
      '&country=' + gadgets.container.country_ +
      '&lang=' + gadgets.container.language_ +
      '&view=' + gadgets.container.view_ +
      (this.specVersion ? '&v=' + this.specVersion : '') +
      (gadgets.container.parentUrl_ ? '&parent=' + encodeURIComponent(gadgets.container.parentUrl_) : '') +
      (this.debug ? '&debug=1' : '') +
      this.getAdditionalParams() +
      this.getUserPrefsParams() +
      '#rpctoken=' + this.rpcToken +
      (this.secureToken ? '&st=' + this.secureToken : '') +
      (this.viewParams ?
          '&view-params=' +  encodeURIComponent(JSON.stringify(this.viewParams)) : '') +
      (this.hashData ? '&' + this.hashData : '');
};

gadgets.IfrGadget.prototype.getUserPrefsParams = function() {
  var params = '';
  if (this.getUserPrefs()) {
    for(var name in this.getUserPrefs()) {
      var value = this.getUserPref(name);
      params += '&up_' + encodeURIComponent(name) + '=' +
          encodeURIComponent(value);
    }
  }
  return params;
}

gadgets.IfrGadget.prototype.handleToggle = function() {
  var gadgetIframe = document.getElementById(this.getIframeId());
  if (gadgetIframe) {
    var gadgetContent = gadgetIframe.parentNode;
    var display = gadgetContent.style.display;
    gadgetContent.style.display = display ? '' : 'none';
  }
};

gadgets.IfrGadget.prototype.handleOpenUserPrefsDialog = function() {
  if (this.userPrefsDialogContentLoaded) {
    this.showUserPrefsDialog();
  } else {
    var gadget = this;
    var igCallbackName = 'ig_callback_' + this.id;
    window[igCallbackName] = function(userPrefsDialogContent) {
      gadget.userPrefsDialogContentLoaded = true;
      gadget.buildUserPrefsDialog(userPrefsDialogContent);
      gadget.showUserPrefsDialog();
    };

    var script = document.createElement('script');
    script.src = 'http://gmodules.com/ig/gadgetsettings?url=' + this.specUrl +
        '&mid=' + this.id + '&output=js' + this.getUserPrefsParams();
    document.body.appendChild(script);
  }
};

gadgets.IfrGadget.prototype.buildUserPrefsDialog = function(content) {
  var userPrefsDialog = document.getElementById(this.getUserPrefsDialogId());
  userPrefsDialog.innerHTML = content +
      '<div class="' + this.cssClassGadgetUserPrefsDialogActionBar +
      '"><input type="button" value="Save" onclick="gadgets.container.getGadget(' +
      this.id +').handleSaveUserPrefs()"> <input type="button" value="Cancel" onclick="gadgets.container.getGadget(' +
      this.id +').handleCancelUserPrefs()"></div>';
  userPrefsDialog.childNodes[0].style.display = '';
};

gadgets.IfrGadget.prototype.showUserPrefsDialog = function(opt_show) {
  var userPrefsDialog = document.getElementById(this.getUserPrefsDialogId());
  userPrefsDialog.style.display = (opt_show || opt_show == undefined)
      ? '' : 'none';
}

gadgets.IfrGadget.prototype.hideUserPrefsDialog = function() {
  this.showUserPrefsDialog(false);
};

gadgets.IfrGadget.prototype.handleSaveUserPrefs = function() {
  this.hideUserPrefsDialog();

  var prefs = {};
  var numFields = document.getElementById('m_' + this.id +
      '_numfields').value;
  for (var i = 0; i < numFields; i++) {
    var input = document.getElementById('m_' + this.id + '_' + i);
    if (input.type != 'hidden') {
      var userPrefNamePrefix = 'm_' + this.id + '_up_';
      var userPrefName = input.name.substring(userPrefNamePrefix.length);
      var userPrefValue = input.value;
      prefs[userPrefName] = userPrefValue;
    }
  }

  this.setUserPrefs(prefs);
  this.refresh();
};

gadgets.IfrGadget.prototype.handleCancelUserPrefs = function() {
  this.hideUserPrefsDialog();
};

gadgets.IfrGadget.prototype.refresh = function() {
  var iframeId = this.getIframeId();
  document.getElementById(iframeId).src = this.getIframeUrl();
};


// ---------
// Container

/**
 * Container interface.
 * @constructor
 */
gadgets.Container = function() {
  this.gadgets_ = {};
  this.parentUrl_ = 'http://' + document.location.host;
  this.country_ = 'ALL';
  this.language_ = 'ALL';
  this.view_ = 'default';
  this.nocache_ = 1;

  // signed max int
  this.maxheight_ = 0x7FFFFFFF;
};

gadgets.Container.inherits(gadgets.Extensible);

/**
 * Known dependencies:
 *     gadgetClass: constructor to create a new gadget instance
 *     userPrefStore: instance of a subclass of gadgets.UserPrefStore
 *     gadgetService: instance of a subclass of gadgets.GadgetService
 *     layoutManager: instance of a subclass of gadgets.LayoutManager
 */

gadgets.Container.prototype.gadgetClass = gadgets.Gadget;

gadgets.Container.prototype.userPrefStore = new gadgets.DefaultUserPrefStore();

gadgets.Container.prototype.gadgetService = new gadgets.GadgetService();

gadgets.Container.prototype.layoutManager =
    new gadgets.StaticLayoutManager();

gadgets.Container.prototype.setParentUrl = function(url) {
  this.parentUrl_ = url;
};

gadgets.Container.prototype.setCountry = function(country) {
  this.country_ = country;
};

gadgets.Container.prototype.setNoCache = function(nocache) {
  this.nocache_ = nocache;
};

gadgets.Container.prototype.setLanguage = function(language) {
  this.language_ = language;
};

gadgets.Container.prototype.setView = function(view) {
  this.view_ = view;
};

gadgets.Container.prototype.setMaxHeight = function(maxheight) {
  this.maxheight_ = maxheight;
};

gadgets.Container.prototype.getGadgetKey_ = function(instanceId) {
  return 'gadget_' + instanceId;
};

gadgets.Container.prototype.getGadget = function(instanceId) {
  return this.gadgets_[this.getGadgetKey_(instanceId)];
};

gadgets.Container.prototype.createGadget = function(opt_params) {
  return new this.gadgetClass(opt_params);
};

gadgets.Container.prototype.addGadget = function(gadget) {
  gadget.id = this.getNextGadgetInstanceId();
  gadget.setUserPrefs(this.userPrefStore.getPrefs(gadget));
  this.gadgets_[this.getGadgetKey_(gadget.id)] = gadget;
};

gadgets.Container.prototype.addGadgets = function(gadgets) {
  for (var i = 0; i < gadgets.length; i++) {
    this.addGadget(gadgets[i]);
  }
};

/**
 * Renders all gadgets in the container.
 */
gadgets.Container.prototype.renderGadgets = function() {
  for (var key in this.gadgets_) {
    this.renderGadget(this.gadgets_[key]);
  }
};

/**
 * Renders a gadget.  Gadgets are rendered inside their chrome element.
 * @param {Object} gadget Gadget object
 */
gadgets.Container.prototype.renderGadget = function(gadget) {
  throw Error(gadgets.error.SUBCLASS_RESPONSIBILITY);
};

gadgets.Container.prototype.nextGadgetInstanceId_ = 0;

gadgets.Container.prototype.getNextGadgetInstanceId = function() {
  return this.nextGadgetInstanceId_++;
};

/**
 * Refresh all the gadgets in the container.
 */
gadgets.Container.prototype.refreshGadgets = function() {
  for (var key in this.gadgets_) {
    this.gadgets_[key].refresh();
  }
};


// ------------
// IfrContainer

/**
 * Container that renders gadget using ifr.
 * @constructor
 */
gadgets.IfrContainer = function() {
  gadgets.Container.call(this);
};

gadgets.IfrContainer.inherits(gadgets.Container);

gadgets.IfrContainer.prototype.gadgetClass = gadgets.IfrGadget;

gadgets.IfrContainer.prototype.gadgetService = new gadgets.IfrGadgetService();

gadgets.IfrContainer.prototype.setParentUrl = function(url) {
  if (!url.match(/^http[s]?:\/\//)) {
    url = document.location.href.match(/^[^?#]+\//)[0] + url;
  }

  this.parentUrl_ = url;
};

/**
 * Renders a gadget using ifr.
 * @param {Object} gadget Gadget object
 */
gadgets.IfrContainer.prototype.renderGadget = function(gadget) {
  var chrome = this.layoutManager.getGadgetChrome(gadget);
  gadget.render(chrome);
};

/**
 * Default container.
 */
gadgets.container = new gadgets.IfrContainer();
/**
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 */

/**
 * @fileoverview Implements the gadgets.UserPrefStore interface using a cookies
 * based implementation. Depends on cookies.js. This code should not be used in
 * a production environment.
 */

/**
 * Cookie-based user preference store.
 * @constructor
 */
gadgets.CookieBasedUserPrefStore = function() {
  gadgets.UserPrefStore.call(this);
};

gadgets.CookieBasedUserPrefStore.inherits(gadgets.UserPrefStore);

gadgets.CookieBasedUserPrefStore.prototype.USER_PREFS_PREFIX =
    'gadgetUserPrefs-';

gadgets.CookieBasedUserPrefStore.prototype.getPrefs = function(gadget) {
  var userPrefs = {};
  var cookieName = this.USER_PREFS_PREFIX + gadget.id;
  var cookie = goog.net.cookies.get(cookieName);
  if (cookie) {
    var pairs = cookie.split('&');
    for (var i = 0; i < pairs.length; i++) {
      var nameValue = pairs[i].split('=');
      var name = decodeURIComponent(nameValue[0]);
      var value = decodeURIComponent(nameValue[1]);
      userPrefs[name] = value;
    }
  }

  return userPrefs;
};

gadgets.CookieBasedUserPrefStore.prototype.savePrefs = function(gadget) {
  var pairs = [];
  for (var name in gadget.getUserPrefs()) {
    var value = gadget.getUserPref(name);
    var pair = encodeURIComponent(name) + '=' + encodeURIComponent(value);
    pairs.push(pair);
  }

  var cookieName = this.USER_PREFS_PREFIX + gadget.id;
  var cookieValue = pairs.join('&');
  goog.net.cookies.set(cookieName, cookieValue);
};

gadgets.Container.prototype.userPrefStore =
    new gadgets.CookieBasedUserPrefStore();