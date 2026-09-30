import 'dart:convert';

import 'package:http/http.dart' as http;

class ApiService {
  static const String _baseUrl =
      'http://localhost/akouss-kalara-php/api';

  static Future<dynamic> get(String endpoint) async {
    final uri = Uri.parse('$_baseUrl/$endpoint');

    final response = await http.get(
      uri,
      headers: {
        'Accept': 'application/json',
      },
    );

    return _traiterReponse(response);
  }

  static Future<dynamic> getWithToken(
    String endpoint,
    String token,
  ) async {
    final uri = Uri.parse('$_baseUrl/$endpoint');

    final response = await http.get(
      uri,
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    return _traiterReponse(response);
  }

  static Future<dynamic> post(
    String endpoint, {
    Map<String, dynamic>? body,
    String? token,
  }) async {
    final uri = Uri.parse('$_baseUrl/$endpoint');

    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }

    final response = await http.post(
      uri,
      headers: headers,
      body: body == null ? null : jsonEncode(body),
    );

    return _traiterReponse(response);
  }

  static dynamic _traiterReponse(http.Response response) {
    dynamic data;

    try {
      data = jsonDecode(response.body);
    } catch (_) {
      throw Exception(
        'Réponse invalide du serveur (${response.statusCode}).',
      );
    }

    if (response.statusCode >= 200 &&
        response.statusCode < 300) {
      return data;
    }

    String message =
        'Erreur serveur (${response.statusCode}).';

    if (data is Map<String, dynamic> &&
        data['erreur'] != null) {
      message = data['erreur'].toString();
    }

    throw Exception(message);
  }

  static Future<List<dynamic>> getCategories() async {
    final data = await get(
      'catalogue/categories.php',
    );

    if (data is List) {
      return data;
    }

    throw Exception(
      'Format inattendu pour les catégories.',
    );
  }

  static Future<List<dynamic>> getBooks({
    String? query,
    int? categoryId,
  }) async {
    final params = <String, String>{};

    if (query != null &&
        query.trim().isNotEmpty) {
      params['q'] = query.trim();
    }

    if (categoryId != null) {
      params['categorie'] = categoryId.toString();
    }

    final uri = Uri.parse(
      '$_baseUrl/catalogue/livres.php',
    ).replace(
      queryParameters:
          params.isEmpty ? null : params,
    );

    final response = await http.get(
      uri,
      headers: {
        'Accept': 'application/json',
      },
    );

    final data = _traiterReponse(response);

    if (data is List) {
      return data;
    }

    throw Exception(
      'Format inattendu pour les livres.',
    );
  }

  static Future<Map<String, dynamic>> getBookDetail(
    int bookId,
  ) async {
    final uri = Uri.parse(
      '$_baseUrl/catalogue/livre_detail.php',
    ).replace(
      queryParameters: {
        'id': bookId.toString(),
      },
    );

    final response = await http.get(
      uri,
      headers: {
        'Accept': 'application/json',
      },
    );

    final data = _traiterReponse(response);

    if (data is Map<String, dynamic>) {
      return data;
    }

    throw Exception(
      'Format inattendu pour le détail du livre.',
    );
  }

  static Future<Map<String, dynamic>> getCart(
    String token,
  ) async {
    final data = await getWithToken(
      'panier/panier.php',
      token,
    );

    if (data is Map<String, dynamic>) {
      return data;
    }

    throw Exception(
      'Format inattendu pour le panier.',
    );
  }

  static Future<Map<String, dynamic>> addToCart({
    required String token,
    required int offerId,
    int quantity = 1,
  }) async {
    final data = await post(
      'panier/ajouter.php',
      token: token,
      body: {
        'offre_id': offerId,
        'quantite': quantity,
      },
    );

    if (data is Map<String, dynamic>) {
      return data;
    }

    throw Exception(
      'Format inattendu lors de l’ajout au panier.',
    );
  }

  static Future<Map<String, dynamic>> updateCartItem({
    required String token,
    required int lineId,
    required int quantity,
  }) async {
    final data = await post(
      'panier/modifier.php',
      token: token,
      body: {
        'ligne_id': lineId,
        'quantite': quantity,
      },
    );

    if (data is Map<String, dynamic>) {
      return data;
    }

    throw Exception(
      'Format inattendu lors de la modification du panier.',
    );
  }

  static Future<Map<String, dynamic>> removeCartItem({
    required String token,
    required int lineId,
  }) async {
    final data = await post(
      'panier/supprimer.php?ligne_id=$lineId',
      token: token,
    );

    if (data is Map<String, dynamic>) {
      return data;
    }

    throw Exception(
      'Format inattendu lors de la suppression du panier.',
    );
  }

  static Future<Map<String, dynamic>> createOrder({
    required String token,
    String deliveryMode = 'domicile',
  }) async {
    final data = await post(
      'commandes/creer.php',
      token: token,
      body: {
        'mode_livraison': deliveryMode,
      },
    );

    if (data is Map<String, dynamic>) {
      return data;
    }

    throw Exception(
      'Format inattendu lors de la création de la commande.',
    );
  }

  static Future<Map<String, dynamic>> initierPaiement({
    required String token,
    required int orderId,
    required String provider,
    required String phone,
  }) async {
    final data = await post(
      'paiement/initier.php',
      token: token,
      body: {
        'commande_id': orderId,
        'fournisseur': provider,
        'telephone': phone,
      },
    );

    if (data is Map<String, dynamic>) {
      return data;
    }

    throw Exception(
      'Format inattendu lors de l’initiation du paiement.',
    );
  }

    static Future<Map<String, dynamic>> simulerPaiement({
    required String token,
    required int orderId,
    required String provider,
    required String phone,
    required String status,
  }) async {
    final data = await post(
      'paiement/simuler.php',
      token: token,
      body: {
        'commande_id': orderId,
        'fournisseur': provider,
        'telephone': phone,
        'statut': status,
      },
    );

    if (data is Map<String, dynamic>) {
      return data;
    }

    throw Exception(
      'Format inattendu lors de la simulation du paiement.',
    );
  }

  static Future<Map<String, dynamic>> getPaymentStatus({
    required String token,
    required int orderId,
  }) async {
    final data = await getWithToken(
      'paiement/statut.php?commande_id=$orderId',
      token,
    );

    if (data is Map<String, dynamic>) {
      return data;
    }

    throw Exception(
      'Format inattendu pour le statut du paiement.',
    );
  }

  static Future<Map<String, dynamic>> register({
    required String email,
    required String password,
    required String fullName,
    String phone = '',
    bool becomeSeller = false,
    String shopName = '',
  }) async {
    final data = await post(
      'auth/inscription.php',
      body: {
        'email': email,
        'mot_de_passe': password,
        'nom_complet': fullName,
        'telephone': phone,
        'devenir_vendeur': becomeSeller,
        'nom_boutique': shopName,
      },
    );

    if (data is Map<String, dynamic>) {
      return data;
    }

    throw Exception(
      'Réponse inattendue du serveur.',
    );
  }

  static Future<List<dynamic>> getMyOrders(
    String token,
  ) async {
    final data = await getWithToken(
      'commandes/mes_commandes.php',
      token,
    );

    if (data is List) {
      return data;
    }

    throw Exception(
      'Format inattendu pour les commandes.',
    );
  }
}
