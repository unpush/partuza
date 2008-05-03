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

