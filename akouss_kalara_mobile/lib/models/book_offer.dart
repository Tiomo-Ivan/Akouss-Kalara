class BookOffer {
  final int id;
  final int livreId;
  final int vendeurId;
  final String type;
  final double prix;
  final int? quantiteStock;
  final String? etatArticle;
  final String? fichierNumerique;
  final String? statutDroits;
  final String? justificatifDroits;
  final String statutModeration;
  final String? dateCreation;
  final String vendeurNom;

  const BookOffer({
    required this.id,
    required this.livreId,
    required this.vendeurId,
    required this.type,
    required this.prix,
    this.quantiteStock,
    this.etatArticle,
    this.fichierNumerique,
    this.statutDroits,
    this.justificatifDroits,
    required this.statutModeration,
    this.dateCreation,
    required this.vendeurNom,
  });

  factory BookOffer.fromJson(Map<String, dynamic> json) {
    return BookOffer(
      id: int.parse(json['id'].toString()),
      livreId: int.parse(json['livre_id'].toString()),
      vendeurId: int.parse(json['vendeur_id'].toString()),
      type: json['type']?.toString() ?? '',
      prix: double.tryParse(json['prix'].toString()) ?? 0,
      quantiteStock: json['quantite_stock'] != null
          ? int.tryParse(json['quantite_stock'].toString())
          : null,
      etatArticle: json['etat_article']?.toString(),
      fichierNumerique: json['fichier_numerique']?.toString(),
      statutDroits: json['statut_droits']?.toString(),
      justificatifDroits: json['justificatif_droits']?.toString(),
      statutModeration: json['statut_moderation']?.toString() ?? '',
      dateCreation: json['date_creation']?.toString(),
      vendeurNom: json['vendeur_nom']?.toString() ?? '',
    );
  }

  String get formattedPrice {
    return '${prix.toStringAsFixed(0)} FCFA';
  }

  bool get isPhysical => type == 'physique';

  bool get isDigital => type == 'numerique';
}