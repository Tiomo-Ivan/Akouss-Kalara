class Book {
  final int id;
  final String title;
  final String author;
  final String category;
  final double? priceMin;
  final int offerCount;
  final String? coverImage;

  const Book({
    required this.id,
    required this.title,
    required this.author,
    required this.category,
    this.priceMin,
    this.offerCount = 0,
    this.coverImage,
    String? price,
  }) : _legacyPrice = price;

  final String? _legacyPrice;

  factory Book.fromJson(Map<String, dynamic> json) {
    return Book(
      id: int.parse(json['id'].toString()),
      title: json['titre']?.toString() ?? '',
      author: json['auteur']?.toString() ?? '',
      category: json['categorie_nom']?.toString() ?? '',
      priceMin: json['prix_min'] != null
          ? double.tryParse(json['prix_min'].toString())
          : null,
      offerCount:
          int.tryParse(json['nombre_offres']?.toString() ?? '0') ?? 0,
      coverImage: json['image_couverture']?.toString(),
    );
  }

  String get formattedPrice {
    if (priceMin != null) {
      return '${priceMin!.toStringAsFixed(0)} FCFA';
    }

    if (_legacyPrice != null && _legacyPrice.isNotEmpty) {
    return _legacyPrice;
    }

    return 'Prix non disponible';
  }

  String get price {
    return formattedPrice;
  }
}