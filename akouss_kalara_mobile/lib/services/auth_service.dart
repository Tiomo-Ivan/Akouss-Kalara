import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import 'api_service.dart';

class AuthService {
  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'auth_user';

  static Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final data = await ApiService.post(
      'auth/connexion.php',
      body: {
        'email': email,
        'mot_de_passe': password,
      },
    );

    if (data is! Map<String, dynamic>) {
      throw Exception('Réponse inattendue du serveur.');
    }

    final token = data['jeton']?.toString();

    if (token == null || token.isEmpty) {
      throw Exception('Jeton de connexion manquant.');
    }

    final user = data['utilisateur'];

    final preferences = await SharedPreferences.getInstance();

    await preferences.setString(_tokenKey, token);

    if (user is Map) {
      await preferences.setString(
        _userKey,
        jsonEncode(Map<String, dynamic>.from(user)),
      );
    }

    return data;
  }

  static Future<String?> getToken() async {
    final preferences = await SharedPreferences.getInstance();
    return preferences.getString(_tokenKey);
  }

  static Future<Map<String, dynamic>?> getUser() async {
    final preferences = await SharedPreferences.getInstance();

    final userJson = preferences.getString(_userKey);

    if (userJson == null || userJson.isEmpty) {
      return null;
    }

    try {
      final decoded = jsonDecode(userJson);

      if (decoded is Map) {
        return Map<String, dynamic>.from(decoded);
      }

      return null;
    } catch (_) {
      return null;
    }
  }

  static Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  static Future<void> logout() async {
    final preferences = await SharedPreferences.getInstance();

    await preferences.remove(_tokenKey);
    await preferences.remove(_userKey);
  }
}